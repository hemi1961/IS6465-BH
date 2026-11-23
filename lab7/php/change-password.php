<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    try {
        changeOwnPassword($pdo, (int)$currentUser['user_id'], $_POST);
        session_regenerate_id(true);
        header('Location: profile.php?password_changed=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to update password right now.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Change Password';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Change Password</h1>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <form method="post" action="change-password.php">
      <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

      <div class="mb-3">
        <label class="form-label" for="current_password">Current Password</label>
        <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
      </div>

      <div class="mb-3">
        <label class="form-label" for="new_password">New Password</label>
        <input class="form-control" id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <div class="form-text">Use at least 8 characters.</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="confirm_password">Confirm New Password</label>
        <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Update Password</button>
        <a class="btn btn-secondary" href="profile.php">Cancel</a>
      </div>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
