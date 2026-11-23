<?php
require_once dirname(__DIR__) . '/repositories/userPDO.php';

define('SESSION_TIMEOUT_SECONDS', 1200); // 20 minutes

function isHttpsRequest()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return false;
}

function startSecureSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (PHP_SAPI === 'cli') {
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        ini_set('session.cache_limiter', '');
    } else {
        $https = isHttpsRequest();
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params(array(
                'lifetime' => 0,
                'path' => '/',
                'secure' => $https,
                'httponly' => true,
                'samesite' => 'Lax'
            ));
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
        }
    }

    session_start();
}

function csrfToken()
{
    startSecureSession();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function isValidCsrfToken($sessionToken, $submittedToken)
{
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }
    if (!is_string($submittedToken) || $submittedToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $submittedToken);
}

function verifyCsrfToken($postData)
{
    startSecureSession();
    $sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
    $submittedToken = (is_array($postData) && isset($postData['csrf_token'])) ? $postData['csrf_token'] : '';

    if (!isValidCsrfToken($sessionToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid or missing CSRF token.');
    }
}

function isSessionExpired($lastActivity, $now = null, $timeoutSeconds = SESSION_TIMEOUT_SECONDS)
{
    $currentTime = ($now === null) ? time() : (int)$now;
    $last = (int)$lastActivity;
    return ($currentTime - $last) > (int)$timeoutSeconds;
}

function loginUserSession($user)
{
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['display_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logoutSession()
{
    startSecureSession();
    $_SESSION = array();

    if (ini_get('session.use_cookies') && PHP_SAPI !== 'cli') {
        $params = session_get_cookie_params();
        if (PHP_VERSION_ID >= 70300) {
            setcookie(session_name(), '', array(
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => isset($params['samesite']) && $params['samesite'] !== '' ? $params['samesite'] : 'Lax'
            ));
        } else {
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function hasAllowedRole($user, $allowedRoles)
{
    if (!is_array($user) || !isset($user['role'])) {
        return false;
    }
    return in_array($user['role'], $allowedRoles, true);
}

function getAuthenticatedUser($pdo)
{
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $lastActivity = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : 0;
    if (isSessionExpired($lastActivity)) {
        logoutSession();
        return null;
    }

    $user = findUserById($pdo, (int)$_SESSION['user_id']);
    if (!$user || $user['status'] !== 'Active') {
        logoutSession();
        return null;
    }

    $_SESSION['last_activity'] = time();
    $_SESSION['display_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];

    return $user;
}

function requireLogin($pdo)
{
    startSecureSession();

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php', true, 303);
        exit;
    }

    $lastActivity = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : 0;
    if (isSessionExpired($lastActivity)) {
        logoutSession();
        header('Location: login.php?expired=1', true, 303);
        exit;
    }

    $user = findUserById($pdo, (int)$_SESSION['user_id']);
    if (!$user || $user['status'] !== 'Active') {
        logoutSession();
        header('Location: login.php?inactive=1', true, 303);
        exit;
    }

    $_SESSION['last_activity'] = time();
    $_SESSION['display_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];

    return $user;
}

function requireRole($user, $allowedRoles)
{
    if (!hasAllowedRole($user, $allowedRoles)) {
        http_response_code(403);
        exit('You are not authorized to perform this action.');
    }
}
