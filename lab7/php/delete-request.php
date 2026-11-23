<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Student', 'Administrator'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST);

    $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Invalid ID');
    }

    try {
        $ok = cancelRequestForActor($pdo, $currentUser, $id);
        if (!$ok) {
            http_response_code(409);
            exit('Request not found, not authorized, or not Open');
        }
        header('Location: request-details.php?request_id=' . (int)$id, true, 303);
        exit;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit('Unable to cancel');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

$id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid ID');
}

$r = findRequestForActor($pdo, $currentUser, $id);
if (!$r) {
    http_response_code(404);
    exit('Request not found.');
}

$pageTitle = 'Cancel Request #' . (int)$id;
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="auth-card">
    <h1>Cancel tutoring request</h1>
    <p>Cancel request #<?= (int)$id ?> for <strong><?= escapeHtml($r['course']) ?></strong>? The record will be retained with status <strong>Cancelled</strong>.</p>

    <?php if ($r['status'] === 'Open'): ?>
      <form method="post" action="delete-request.php">
        <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
        <input type="hidden" name="request_id" value="<?= (int)$id ?>">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-danger">Yes, cancel request</button>
          <a class="btn btn-secondary" href="request-details.php?request_id=<?= (int)$id ?>">Keep request</a>
        </div>
      </form>
    <?php else: ?>
      <div class="alert alert-warning">Only Open requests can be cancelled.</div>
      <a class="btn btn-secondary" href="request-details.php?request_id=<?= (int)$id ?>">Back to request</a>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
