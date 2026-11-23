<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);

$error = '';
$formFullName = $currentUser['full_name'];
$formEmail = $currentUser['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $formFullName = trim((string)(isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $formEmail = trim((string)(isset($_POST['email']) ? $_POST['email'] : ''));

    try {
        $updated = updateOwnProfile($pdo, (int)$currentUser['user_id'], $_POST);
        $_SESSION['display_name'] = $updated['full_name'];
        header('Location: profile.php?updated=1', true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        $error = 'Unable to update your profile right now.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed.');
}

$pageTitle = 'Edit Profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Edit Profile</h1>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <form method="post" action="edit-profile.php">
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
        <label class="form-label">Role (read only)</label>
        <input class="form-control" type="text" value="<?= escapeHtml($currentUser['role']) ?>" disabled readonly>
      </div>

      <div class="mb-3">
        <label class="form-label">Status (read only)</label>
        <input class="form-control" type="text" value="<?= escapeHtml($currentUser['status']) ?>" disabled readonly>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a class="btn btn-secondary" href="profile.php">Cancel</a>
      </div>
    </form>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
