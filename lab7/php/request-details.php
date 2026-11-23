<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Student', 'Administrator'));

$id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid request ID');
}

$r = findRequestForActor($pdo, $currentUser, $id);
if (!$r) {
    http_response_code(404);
    exit('Request not found.');
}

$topic = decodeTopic($r['topic']);

$pageTitle = 'Request #' . (int)$id;
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="app-card">
    <h1>Request #<?= (int)$id ?></h1>
    <dl class="row">
      <dt class="col-sm-3">Student</dt>
      <dd class="col-sm-9"><?= escapeHtml($r['student_name']) ?> (<?= escapeHtml($r['student_email']) ?>)</dd>
      <dt class="col-sm-3">Course</dt>
      <dd class="col-sm-9"><?= escapeHtml($r['course']) ?></dd>
      <dt class="col-sm-3">Request details</dt>
      <dd class="col-sm-9"><?= escapeHtml($topic['reason']) ?></dd>
      <dt class="col-sm-3">Topics</dt>
      <dd class="col-sm-9"><?= escapeHtml(implode(', ', $topic['topics'])) ?></dd>
      <dt class="col-sm-3">Preferred date</dt>
      <dd class="col-sm-9"><?= escapeHtml($r['preferred_date']) ?></dd>
      <dt class="col-sm-3">Preferred time</dt>
      <dd class="col-sm-9"><?= escapeHtml($r['preferred_time']) ?></dd>
      <dt class="col-sm-3">Status</dt>
      <dd class="col-sm-9"><?= escapeHtml($r['status']) ?></dd>
    </dl>
    <div class="d-flex flex-wrap gap-2">
      <?php if ($r['status'] === 'Open'): ?>
        <a class="btn btn-primary" href="edit-request.php?request_id=<?= (int)$id ?>">Edit</a>
        <a class="btn btn-outline-danger" href="delete-request.php?request_id=<?= (int)$id ?>">Cancel request</a>
      <?php endif; ?>
      <a href="requests.php" class="btn btn-secondary">
        <?= $currentUser['role'] === 'Administrator' ? 'All requests' : 'My requests' ?>
      </a>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
