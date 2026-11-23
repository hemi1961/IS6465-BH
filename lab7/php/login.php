<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
startSecureSession();

if (!empty($_SESSION['user_id']) && getAuthenticatedUser($pdo) !== null) {
    header('Location: profile.php', true, 303);
    exit;
}

$error = '';
$notice = '';
$formEmail = '';

if (isset($_GET['logged_out'])) {
    $notice = 'You have been logged out.';
} elseif (isset($_GET['expired'])) {
    $notice = 'Your session expired due to inactivity. Please log in again.';
} elseif (isset($_GET['inactive'])) {
    $notice = 'Your session ended because the account is no longer active.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $formEmail = trim((string)(isset($_POST['email']) ? $_POST['email'] : ''));
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');

    try {
        $authenticated = authenticateUser($pdo, $formEmail, $password);
        if (!$authenticated) {
            http_response_code(401);
            $error = 'Invalid email or password.';
        } else {
            loginUserSession($authenticated);
            header('Location: profile.php', true, 303);
            exit;
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to log in right now. Please try again.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Log In';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Log In</h1>

    <?php if ($notice !== ''): ?>
      <div class="alert alert-info" role="alert"><?= escapeHtml($notice) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" maxlength="254" value="<?= escapeHtml($formEmail) ?>" autocomplete="username" required>
      </div>

      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Log In</button>
    </form>

    <p class="mt-3 mb-0 text-center text-muted">
      New Student or Tutor? <a href="register.php">Create an account</a>
    </p>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
