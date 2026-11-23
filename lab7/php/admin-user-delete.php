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

$error = '';
$relatedCount = (int)$targetUser['related_request_count'];
$isSelf = ((int)$targetUser['user_id'] === (int)$currentUser['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    $confirmed = (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes');

    try {
        deleteUserAccountForAdmin($pdo, $currentUser, $targetId, $confirmed);
        header('Location: admin-users.php?deleted=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        http_response_code(409);
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to delete user account.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Delete User Account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Delete Account Confirmation</h1>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <p>Delete account for <strong><?= escapeHtml($targetUser['full_name']) ?></strong> (<?= escapeHtml($targetUser['email']) ?>)?</p>
    <p class="text-muted">Role: <?= escapeHtml($targetUser['role']) ?> | Status: <?= escapeHtml($targetUser['status']) ?></p>

    <?php if ($relatedCount > 0): ?>
      <div class="alert alert-warning">
        <strong>Deletion blocked by retention policy:</strong> This user has <strong><?= $relatedCount ?></strong> related tutoring request record(s). Dependent tutoring records are never silently removed. Please deactivate the account instead.
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-warning" href="admin-user-status.php?user_id=<?= (int)$targetUser['user_id'] ?>">Deactivate Account Instead</a>
        <a class="btn btn-secondary" href="admin-user-detail.php?user_id=<?= (int)$targetUser['user_id'] ?>">Cancel</a>
      </div>
    <?php elseif ($isSelf): ?>
      <div class="alert alert-warning">
        You cannot delete your own currently signed-in administrator account.
      </div>
      <a class="btn btn-secondary" href="admin-user-detail.php?user_id=<?= (int)$targetUser['user_id'] ?>">Cancel</a>
    <?php else: ?>
      <div class="alert alert-danger">
        <strong>Warning:</strong> This account has 0 related tutoring requests. Permanent deletion cannot be undone.
      </div>
      <form method="post" action="admin-user-delete.php">
        <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
        <input type="hidden" name="user_id" value="<?= (int)$targetUser['user_id'] ?>">
        <input type="hidden" name="confirm_delete" value="yes">

        <div class="d-flex gap-2">
          <a class="btn btn-secondary" href="admin-user-detail.php?user_id=<?= (int)$targetUser['user_id'] ?>">Cancel</a>
          <button type="submit" class="btn btn-danger">Confirm Delete</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
