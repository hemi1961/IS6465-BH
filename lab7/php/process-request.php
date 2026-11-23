<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/requestService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required');
}

$pdo = openDatabase();
$currentUser = requireLogin($pdo);
requireRole($currentUser, array('Student'));
verifyCsrfToken($_POST);

if ((isset($_POST['form_name']) ? $_POST['form_name'] : '') !== 'tutoring_request') {
    http_response_code(400);
    exit('Invalid form');
}

try {
    $id = submitRequestForStudent($pdo, $currentUser, $_POST);
    header('Location: request-details.php?request_id=' . (int)$id, true, 303);
    exit;
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    $pageTitle = 'Request Could Not Be Submitted';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/nav.php';
    echo '<main class="container py-4"><div class="app-card">';
    echo '<h1>Request could not be submitted</h1>';
    echo '<div class="alert alert-danger">' . escapeHtml($e->getMessage()) . '</div>';
    echo '<a class="btn btn-primary" href="request-form.php">Return to form</a>';
    echo '</div></main>';
    require __DIR__ . '/../includes/footer.php';
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Unable to save request');
}
