<?php
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/auth.php';
startSecureSession();

$navUser = isset($currentUser) && is_array($currentUser) ? $currentUser : null;
$navRole = $navUser ? $navUser['role'] : (isset($_SESSION['role']) ? $_SESSION['role'] : null);
$navName = $navUser ? $navUser['full_name'] : (isset($_SESSION['display_name']) ? $_SESSION['display_name'] : null);
$isLoggedIn = !empty($_SESSION['user_id']) && $navRole !== null;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="<?= $isLoggedIn ? 'profile.php' : 'login.php' ?>">Peer Tutoring Hub</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <div class="navbar-nav me-auto">
        <?php if ($isLoggedIn): ?>
          <a class="nav-link" href="profile.php">My Profile</a>
          <?php if ($navRole === 'Student'): ?>
            <a class="nav-link" href="requests.php">My Requests</a>
            <a class="nav-link" href="request-form.php">Request a Session</a>
          <?php elseif ($navRole === 'Administrator'): ?>
            <a class="nav-link" href="requests.php">All Requests</a>
            <a class="nav-link" href="admin-users.php">Manage Users</a>
          <?php endif; ?>
        <?php else: ?>
          <a class="nav-link" href="login.php">Log In</a>
          <a class="nav-link" href="register.php">Create Account</a>
        <?php endif; ?>
      </div>
      <?php if ($isLoggedIn): ?>
        <div class="d-flex align-items-center gap-3 text-white">
          <span class="small">
            Signed in as <strong><?= escapeHtml($navName) ?></strong> (<?= escapeHtml($navRole) ?>)
          </span>
          <form method="post" action="logout.php" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
            <button type="submit" class="btn btn-outline-light btn-sm">Log Out</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>
