<?php
/**
 * Validation Unit Tests (Lab 4 + Lab 7 User Validation)
 * Run from the lab7 directory:
 *   php tests/validation-tests.php
 */
require_once dirname(__DIR__) . '/includes/validation.php';

$passed = 0;
$failed = 0;

function reportValidationTest($name, $actual, $expected)
{
    global $passed, $failed;
    $ok = ($actual === $expected);
    if ($ok) {
        $passed++;
    } else {
        $failed++;
    }
    $line = ($ok ? 'PASS: ' : 'FAIL: ') . $name;
    if (PHP_SAPI === 'cli') {
        echo $line . PHP_EOL;
    } else {
        echo '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
    }
}

// Lab 4: validatePreferredTime
$timeTests = array(
    array('name' => 'PreferredTime: Valid time', 'input' => 'Morning', 'expected' => ''),
    array('name' => 'PreferredTime: Missing time', 'input' => '', 'expected' => 'Choose a valid preferred time.'),
    array('name' => 'PreferredTime: Altered value', 'input' => 'Midnight', 'expected' => 'Choose a valid preferred time.')
);
foreach ($timeTests as $t) {
    reportValidationTest($t['name'], validatePreferredTime($t['input']), $t['expected']);
}

// Lab 4: validateTopics
$topicTests = array(
    array('name' => 'Topics: Multiple valid topics', 'input' => array('Exam review', 'Study plan'), 'expected' => ''),
    array('name' => 'Topics: Missing topics', 'input' => array(), 'expected' => 'Select at least one valid topic.'),
    array('name' => 'Topics: Altered topic', 'input' => array('Free tutoring'), 'expected' => 'Select at least one valid topic.')
);
foreach ($topicTests as $t) {
    reportValidationTest($t['name'], validateTopics($t['input']), $t['expected']);
}

// Lab 4: validateReason
$reasonTests = array(
    array('name' => 'Reason: Minimum length', 'input' => '1234567890', 'expected' => ''),
    array('name' => 'Reason: Maximum length', 'input' => str_repeat('a', 500), 'expected' => ''),
    array('name' => 'Reason: Too short', 'input' => '123456789', 'expected' => 'Describe your request using 10 to 500 characters.'),
    array('name' => 'Reason: Too long', 'input' => str_repeat('a', 501), 'expected' => 'Describe your request using 10 to 500 characters.')
);
foreach ($reasonTests as $t) {
    reportValidationTest($t['name'], validateReason($t['input']), $t['expected']);
}

// Lab 7: User validation helpers
reportValidationTest('FullName: Valid', validateFullName('Alex Morgan'), '');
reportValidationTest('FullName: Too short', validateFullName('A'), 'Full name must be between 2 and 120 characters.');
reportValidationTest('Email: Valid', validateEmailAddress('Alex@Example.EDU'), '');
reportValidationTest('Email: Invalid', validateEmailAddress('not-an-email'), 'Enter a valid email address.');
reportValidationTest('Password: Valid 8+ chars', validatePasswordStrength('Secret123!'), '');
reportValidationTest('Password: Too short', validatePasswordStrength('1234567'), 'Password must be at least 8 characters long.');
reportValidationTest('PublicRole: Student allowed', validateRegistrationRole('Student'), '');
reportValidationTest('PublicRole: Tutor allowed', validateRegistrationRole('Tutor'), '');
reportValidationTest('PublicRole: Administrator blocked', validateRegistrationRole('Administrator'), 'Public registration is limited to Student or Tutor accounts.');

if (PHP_SAPI === 'cli') {
    echo 'Results: ' . $passed . ' passed, ' . $failed . ' failed' . PHP_EOL;
    exit($failed > 0 ? 1 : 0);
}
