<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Student', 'Administrator'));

$course = trim((string)(isset($_GET['course']) ? $_GET['course'] : ''));
$status = trim((string)(isset($_GET['status']) ? $_GET['status'] : ''));

if ($course !== '' && !in_array($course, array('IS 6465', 'IS 6495', 'IS 6570'), true)) {
    http_response_code(400);
    exit('Invalid course');
}
if ($status !== '' && !in_array($status, array('Open', 'Cancelled', 'Assigned', 'Completed'), true)) {
    http_response_code(400);
    exit('Invalid status');
}

$rows = listRequestsForActor($pdo, $currentUser, $course, $status);
$isAdmin = ($currentUser['role'] === 'Administrator');

$pageTitle = $isAdmin ? 'All Tutoring Requests' : 'My Tutoring Requests';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="app-card">
    <h1><?= escapeHtml($pageTitle) ?></h1>

    <form method="get" action="requests.php" class="row g-2 mb-3">
      <div class="col-md-5">
        <select class="form-select" name="course">
          <option value="">All courses</option>
          <?php foreach (array('IS 6465', 'IS 6495', 'IS 6570') as $c): ?>
            <option value="<?= escapeHtml($c) ?>" <?= $c === $course ? 'selected' : '' ?>><?= escapeHtml($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <select class="form-select" name="status">
          <option value="">All statuses</option>
          <?php foreach (array('Open', 'Cancelled', 'Assigned', 'Completed') as $st): ?>
            <option value="<?= escapeHtml($st) ?>" <?= $st === $status ? 'selected' : '' ?>><?= escapeHtml($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
        <a class="btn btn-outline-secondary" href="requests.php">Reset</a>
      </div>
    </form>

    <?php if (count($rows) === 0): ?>
      <p class="text-muted">No matching requests.</p>
      <?php if (!$isAdmin): ?>
        <a class="btn btn-success" href="request-form.php">Request a Tutoring Session</a>
      <?php endif; ?>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Student</th>
              <th>Course</th>
              <th>Details</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): $topic = decodeTopic($r['topic']); ?>
              <tr>
                <td><?= (int)$r['request_id'] ?></td>
                <td><?= escapeHtml($r['student_name']) ?></td>
                <td><?= escapeHtml($r['course']) ?></td>
                <td><?= escapeHtml($topic['reason']) ?></td>
                <td><?= escapeHtml($r['preferred_date']) ?></td>
                <td><?= escapeHtml($r['preferred_time']) ?></td>
                <td><?= escapeHtml($r['status']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="request-details.php?request_id=<?= (int)$r['request_id'] ?>">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
