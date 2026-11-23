<?php
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: php/profile.php', true, 302);
    exit;
}

header('Location: php/login.php', true, 302);
exit;
