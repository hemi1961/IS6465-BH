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
$success = '';
$formFullName = '';
$formEmail = '';
$formRole = 'Student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $formFullName = trim((string)(isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $formEmail = trim((string)(isset($_POST['email']) ? $_POST['email'] : ''));
    $formRole = trim((string)(isset($_POST['role']) ? $_POST['role'] : ''));

    try {
        registerUser($pdo, $_POST);
        $success = 'Your account has been created and is Active. You may now log in.';
        $formFullName = '';
        $formEmail = '';
        $formRole = 'Student';
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to create your account right now. Please try again.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Create Account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Create User Account</h1>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="alert alert-success" role="alert">
        <?= escapeHtml($success) ?>
        <div class="mt-2">
          <a class="btn btn-primary btn-sm" href="login.php">Proceed to Log In</a>
        </div>
      </div>
    <?php else: ?>
      <form method="post" action="register.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

        <div class="mb-3">
          <label class="form-label" for="full_name">Full Name</label>
          <input class="form-control" id="full_name" name="full_name" type="text" maxlength="120" value="<?= escapeHtml($formFullName) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" id="email" name="email" type="email" maxlength="254" value="<?= escapeHtml($formEmail) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <div class="form-text">Use at least 8 characters.</div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="confirm_password">Confirm Password</label>
          <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>

        <fieldset class="mb-3">
          <legend class="col-form-label pt-0 fw-bold">Account type</legend>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="role" id="role_student" value="Student" <?= $formRole === 'Student' ? 'checked' : '' ?> required>
            <label class="form-check-label" for="role_student">Student</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="role" id="role_tutor" value="Tutor" <?= $formRole === 'Tutor' ? 'checked' : '' ?> required>
            <label class="form-check-label" for="role_tutor">Tutor</label>
          </div>
        </fieldset>

        <button type="submit" class="btn btn-primary w-100">Create Account</button>
      </form>
      <p class="mt-3 mb-0 text-center text-muted">
        Already have an account? <a href="login.php">Log In</a>
      </p>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
