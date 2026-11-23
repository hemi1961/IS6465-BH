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
$selectedRole = $targetUser['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);
    $selectedRole = trim((string)(isset($_POST['new_role']) ? $_POST['new_role'] : ''));

    try {
        updateUserRoleForAdmin($pdo, $currentUser, $targetId, $selectedRole);
        header('Location: admin-user-detail.php?user_id=' . (int)$targetId . '&role_updated=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to update user role.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Update User Role';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Update User Role</h1>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <p><strong>User:</strong> <?= escapeHtml($targetUser['full_name']) ?> (<?= escapeHtml($targetUser['email']) ?>)</p>
    <p><strong>Current role:</strong> <?= escapeHtml($targetUser['role']) ?></p>

    <form method="post" action="admin-user-role.php">
      <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
      <input type="hidden" name="user_id" value="<?= (int)$targetUser['user_id'] ?>">

      <div class="mb-3">
        <label class="form-label" for="new_role">New role</label>
        <select class="form-select" id="new_role" name="new_role" required>
          <?php foreach (array('Student', 'Tutor', 'Administrator') as $r): ?>
            <option value="<?= escapeHtml($r) ?>" <?= $r === $selectedRole ? 'selected' : '' ?>><?= escapeHtml($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-secondary" href="admin-user-detail.php?user_id=<?= (int)$targetUser['user_id'] ?>">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Role</button>
      </div>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
