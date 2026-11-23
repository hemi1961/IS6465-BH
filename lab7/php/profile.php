<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
$profile = getUserProfile($pdo, (int)$currentUser['user_id']);

if (!$profile) {
    logoutSession();
    header('Location: login.php?inactive=1', true, 303);
    exit;
}

$flash = '';
if (isset($_GET['updated'])) {
    $flash = 'Your profile has been updated.';
} elseif (isset($_GET['password_changed'])) {
    $flash = 'Your password has been updated.';
}

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="app-card">
    <h1>My Profile</h1>

    <?php if ($flash !== ''): ?>
      <div class="alert alert-success" role="alert"><?= escapeHtml($flash) ?></div>
    <?php endif; ?>

    <dl class="row mb-4">
      <dt class="col-sm-3">Name</dt>
      <dd class="col-sm-9"><?= escapeHtml($profile['full_name']) ?></dd>

      <dt class="col-sm-3">Email</dt>
      <dd class="col-sm-9"><?= escapeHtml($profile['email']) ?></dd>

      <dt class="col-sm-3">Role</dt>
      <dd class="col-sm-9">
        <span class="badge bg-primary"><?= escapeHtml($profile['role']) ?></span>
        <span class="text-muted small ms-2">(read only)</span>
      </dd>

      <dt class="col-sm-3">Status</dt>
      <dd class="col-sm-9">
        <span class="badge <?= $profile['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
          <?= escapeHtml($profile['status']) ?>
        </span>
        <span class="text-muted small ms-2">(read only)</span>
      </dd>

      <dt class="col-sm-3">Account Created</dt>
      <dd class="col-sm-9"><?= escapeHtml($profile['created_at']) ?></dd>
    </dl>

    <div class="d-flex flex-wrap gap-2 mb-4">
      <a class="btn btn-primary" href="edit-profile.php">Edit Profile</a>
      <a class="btn btn-outline-primary" href="change-password.php">Change Password</a>
    </div>

    <hr>

    <h2 class="h5 mb-3">Available Actions for Your Role</h2>
    <?php if ($profile['role'] === 'Student'): ?>
      <p class="text-muted">As a Student, you can submit new tutoring requests and view, edit, or cancel your own Open requests.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-success" href="request-form.php">Request a Tutoring Session</a>
        <a class="btn btn-outline-secondary" href="requests.php">View My Requests</a>
      </div>
    <?php elseif ($profile['role'] === 'Administrator'): ?>
      <p class="text-muted">As an Administrator, you can manage user accounts, roles, statuses, and review or manage all tutoring requests.</p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-dark" href="admin-users.php">Manage Users</a>
        <a class="btn btn-outline-secondary" href="requests.php">View All Tutoring Requests</a>
      </div>
    <?php else: ?>
      <p class="text-muted mb-0">
        Your Tutor account is active. You can update your profile and password here. The Tutor tutoring-request workflow is not part of this Lab 7 release.
      </p>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
