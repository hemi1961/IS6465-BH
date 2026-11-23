<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Administrator'));

$targetId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$targetId || $targetId < 1) {
    http_response_code(400);
    exit('Invalid user ID.');
}

$targetUser = getUserDetailsForAdmin($pdo, $currentUser, $targetId);
if (!$targetUser) {
    http_response_code(404);
    exit('User account not found.');
}

$flash = '';
if (isset($_GET['role_updated'])) {
    $flash = 'User role updated successfully.';
} elseif (isset($_GET['status_updated'])) {
    $flash = 'Account status updated successfully.';
}

$pageTitle = 'Manage User #' . (int)$targetUser['user_id'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="app-card">
    <h1>User Account #<?= (int)$targetUser['user_id'] ?></h1>

    <?php if ($flash !== ''): ?>
      <div class="alert alert-success" role="alert"><?= escapeHtml($flash) ?></div>
    <?php endif; ?>

    <dl class="row mb-4">
      <dt class="col-sm-3">Full Name</dt>
      <dd class="col-sm-9"><?= escapeHtml($targetUser['full_name']) ?></dd>

      <dt class="col-sm-3">Email</dt>
      <dd class="col-sm-9"><?= escapeHtml($targetUser['email']) ?></dd>

      <dt class="col-sm-3">Role</dt>
      <dd class="col-sm-9"><?= escapeHtml($targetUser['role']) ?></dd>

      <dt class="col-sm-3">Status</dt>
      <dd class="col-sm-9">
        <span class="badge <?= $targetUser['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
          <?= escapeHtml($targetUser['status']) ?>
        </span>
      </dd>

      <dt class="col-sm-3">Created</dt>
      <dd class="col-sm-9"><?= escapeHtml($targetUser['created_at']) ?></dd>

      <dt class="col-sm-3">Related Tutoring Requests</dt>
      <dd class="col-sm-9"><?= (int)$targetUser['related_request_count'] ?></dd>
    </dl>

    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-primary" href="admin-user-role.php?user_id=<?= (int)$targetUser['user_id'] ?>">Update Role</a>
      <a class="btn btn-warning" href="admin-user-status.php?user_id=<?= (int)$targetUser['user_id'] ?>">
        <?= $targetUser['status'] === 'Active' ? 'Deactivate Account' : 'Activate Account' ?>
      </a>
      <a class="btn btn-outline-danger" href="admin-user-delete.php?user_id=<?= (int)$targetUser['user_id'] ?>">Delete Account</a>
      <a class="btn btn-secondary" href="admin-users.php">Back to Manage Users</a>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
