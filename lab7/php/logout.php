<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required for logout.');
}

startSecureSession();

// Verify CSRF when an active authenticated session exists; if already expired,
// safely clear any residual session state and redirect cleanly.
if (!empty($_SESSION['user_id'])) {
    verifyCsrfToken($_POST);
}

logoutSession();
header('Location: login.php?logged_out=1', true, 303);
exit;
