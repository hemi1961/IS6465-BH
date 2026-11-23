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
    exit('Invalid ID');
}

$r = findRequestForActor($pdo, $currentUser, $id);
if (!$r) {
    http_response_code(404);
    exit('Request not found.');
}

$topic = decodeTopic($r['topic']);

$pageTitle = 'Edit Request #' . (int)$id;
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="app-card">
    <h1>Edit request #<?= (int)$id ?></h1>
    <?php if ($r['status'] !== 'Open'): ?>
      <div class="alert alert-warning">Only Open requests may be edited.</div>
      <a class="btn btn-secondary" href="request-details.php?request_id=<?= (int)$id ?>">Back</a>
    <?php else: ?>
      <form method="post" action="update-request.php">
        <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">
        <input type="hidden" name="request_id" value="<?= (int)$id ?>">

        <div class="mb-3">
          <label class="form-label" for="course">Course</label>
          <select class="form-select" id="course" name="course" required>
            <?php foreach (array('IS 6465', 'IS 6495', 'IS 6570') as $c): ?>
              <option value="<?= escapeHtml($c) ?>" <?= $c === $r['course'] ? 'selected' : '' ?>><?= escapeHtml($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label" for="reason">What would you like help with?</label>
          <textarea class="form-control" id="reason" name="reason" rows="4" minlength="10" maxlength="500" required><?= escapeHtml($topic['reason']) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label" for="preferreddate">Preferred date</label>
          <input class="form-control" id="preferreddate" name="preferreddate" type="date" value="<?= escapeHtml($r['preferred_date']) ?>" required>
        </div>

        <fieldset class="mb-3">
          <legend class="h6">Topics</legend>
          <?php foreach (array('Exam review', 'Practice problems', 'Study plan') as $t): ?>
            <label class="me-3">
              <input type="checkbox" name="topics[]" value="<?= escapeHtml($t) ?>" <?= in_array($t, $topic['topics'], true) ? 'checked' : '' ?>>
              <?= escapeHtml($t) ?>
            </label>
          <?php endforeach; ?>
        </fieldset>

        <fieldset class="mb-3">
          <legend class="h6">Preferred time</legend>
          <?php foreach (array('Morning', 'Afternoon', 'Evening') as $t): ?>
            <label class="me-3">
              <input type="radio" name="preferredtime" value="<?= escapeHtml($t) ?>" <?= $t === $r['preferred_time'] ? 'checked' : '' ?> required>
              <?= escapeHtml($t) ?>
            </label>
          <?php endforeach; ?>
        </fieldset>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <a class="btn btn-secondary" href="request-details.php?request_id=<?= (int)$id ?>">Back</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
