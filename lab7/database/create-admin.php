<?php
/**
 * CLI script to create an Administrator account in database/tutoring.db.
 *
 * Usage (from lab7/database):
 *   php create-admin.php "Test Administrator" admin@example.com "TestPassword123"
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/repositories/userPDO.php';

if (!isset($argv) || count($argv) !== 4) {
    fwrite(
        STDERR,
        'Usage: php create-admin.php "Full Name" email@example.com "Password12CharsMin"' . PHP_EOL
    );
    exit(1);
}

$fullName = trim((string)$argv[1]);
$email = normalizeEmail($argv[2]);
$password = (string)$argv[3];

$errors = array();

$nameError = validateFullName($fullName);
if ($nameError !== '') {
    $errors[] = $nameError;
}

$emailError = validateEmailAddress($email);
if ($emailError !== '') {
    $errors[] = $emailError;
}

if (strlen($password) < 12) {
    $errors[] = 'Administrator password must be at least 12 characters long.';
} elseif (strlen($password) > 72) {
    $errors[] = 'Administrator password must not exceed 72 characters.';
}

if (count($errors) > 0) {
    foreach ($errors as $err) {
        fwrite(STDERR, 'Error: ' . $err . PHP_EOL);
    }
    exit(1);
}

try {
    $pdo = openDatabase();

    $existing = findUserByEmail($pdo, $email);
    if ($existing !== false) {
        fwrite(STDERR, 'Error: An account with email ' . $email . ' already exists.' . PHP_EOL);
        exit(1);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $userId = insertUser($pdo, $fullName, $email, $passwordHash, 'Administrator', 'Active');

    echo 'Administrator account created successfully.' . PHP_EOL;
    echo 'User ID: ' . $userId . PHP_EOL;
    echo 'Name:    ' . $fullName . PHP_EOL;
    echo 'Email:   ' . $email . PHP_EOL;
    echo 'Role:    Administrator' . PHP_EOL;
    echo 'Status:  Active' . PHP_EOL;
    exit(0);
} catch (PDOException $e) {
    if (stripos($e->getMessage(), 'no such table') !== false) {
        fwrite(
            STDERR,
            'Database error: users table not found. Run setup-database.sql first.' . PHP_EOL
        );
    } else {
        fwrite(STDERR, 'Database error: ' . $e->getMessage() . PHP_EOL);
    }
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
