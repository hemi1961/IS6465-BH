<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/userService.php';

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Administrator'));

$search = trim((string)(isset($_GET['q']) ? $_GET['q'] : ''));
$role = trim((string)(isset($_GET['role']) ? $_GET['role'] : ''));
$status = trim((string)(isset($_GET['status']) ? $_GET['status'] : ''));

$error = '';
$users = array();

try {
    $users = listUsersForAdmin($pdo, $currentUser, $search, $role, $status);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    $error = $e->getMessage();
}

$flash = '';
if (isset($_GET['deleted'])) {
    $flash = 'User account deleted.';
}

$pageTitle = 'Manage Users';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/nav.php';
?>
<main class="container py-4">
  <div class="app-card">
    <h1>Manage Users</h1>

    <?php if ($flash !== ''): ?>
      <div class="alert alert-success" role="alert"><?= escapeHtml($flash) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
    <?php endif; ?>

    <form method="get" action="admin-users.php" class="row g-2 mb-4">
      <div class="col-md-5">
        <label class="visually-hidden" for="q">Search</label>
        <input class="form-control" id="q" name="q" type="text" placeholder="Search by name or email..." value="<?= escapeHtml($search) ?>">
      </div>
      <div class="col-md-3">
        <label class="visually-hidden" for="role">Role</label>
        <select class="form-select" id="role" name="role">
          <option value="">All Roles</option>
          <?php foreach (array('Student', 'Tutor', 'Administrator') as $r): ?>
            <option value="<?= escapeHtml($r) ?>" <?= $r === $role ? 'selected' : '' ?>><?= escapeHtml($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="visually-hidden" for="status">Status</label>
        <select class="form-select" id="status" name="status">
          <option value="">All Statuses</option>
          <?php foreach (array('Active', 'Inactive') as $st): ?>
            <option value="<?= escapeHtml($st) ?>" <?= $st === $status ? 'selected' : '' ?>><?= escapeHtml($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-primary flex-grow-1">Search</button>
        <a class="btn btn-outline-secondary" href="admin-users.php">Reset</a>
      </div>
    </form>

    <?php if (count($users) === 0): ?>
      <p class="text-muted mb-0">No user accounts match the current search criteria.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u['user_id'] ?></td>
                <td><?= escapeHtml($u['full_name']) ?></td>
                <td><?= escapeHtml($u['email']) ?></td>
                <td><?= escapeHtml($u['role']) ?></td>
                <td>
                  <span class="badge <?= $u['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                    <?= escapeHtml($u['status']) ?>
                  </span>
                </td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="admin-user-detail.php?user_id=<?= (int)$u['user_id'] ?>">Manage</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
