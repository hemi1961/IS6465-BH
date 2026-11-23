<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Administrator'));

$targetId = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT)
    : filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$targetId || $targetId < 1) {
    http_response_code(400);
    exit('Invalid user ID.');
}

$targetUser = getUserDetailsForAdmin($pdo, $currentUser, $targetId);
if (!$targetUser) {
    http_response_code(404);
    exit('User account not found.');
}

$proposedStatus = ($targetUser['status'] === 'Active') ? 'Inactive' : 'Active';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    $newStatus = trim((string)(isset($_POST['new_status']) ? $_POST['new_status'] : ''));

    try {
        updateUserStatusForAdmin($pdo, $currentUser, $targetId, $newStatus);
        header('Location: admin-user-detail.php?user_id=' . (int)$targetId . '&status_updated=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to update account status.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Update Account Status';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Account Status</h1>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <p><strong>User:</strong> <?= escapeHtml($targetUser['full_name']) ?> (<?= escapeHtml($targetUser['email']) ?>)</p>
    <p><strong>Current status:</strong> <?= escapeHtml($targetUser['status']) ?></p>
    <p><strong>New status:</strong> <?= escapeHtml($proposedStatus) ?></p>

    <?php if ($proposedStatus === 'Inactive'): ?>
      <div class="alert alert-warning">
        Deactivating this account immediately prevents the user from logging in and terminates any active session on their next request.
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        Activating this account restores the user's ability to log in.
      </div>
    <?php endif; ?>

    <form method="post" action="admin-user-status.php">
      <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
      <input type="hidden" name="user_id" value="<?= (int)$targetUser['user_id'] ?>">
      <input type="hidden" name="new_status" value="<?= escapeHtml($proposedStatus) ?>">

      <div class="d-flex gap-2">
        <a class="btn btn-secondary" href="admin-user-detail.php?user_id=<?= (int)$targetUser['user_id'] ?>">Cancel</a>
        <button type="submit" class="btn btn-warning">Confirm <?= escapeHtml($proposedStatus === 'Inactive' ? 'Deactivation' : 'Activation') ?></button>
      </div>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
