<?php
/**
 * Automated PHP CLI Regression Test Harness for UC01 and UC02
 * Verifies that Lab 6 tutoring request creation, validation, revision,
 * cancellation, JOIN retrieval, filtering, and foreign keys still pass
 * under the extended Lab 7 schema.
 *
 * Run from the lab7 directory:
 *   php tests/integration-tests.php
 */
require_once dirname(__DIR__) . '/services/requestService.php';

$passed = 0;
$failed = 0;

function check($ok, $label)
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo 'PASS: ' . $label . PHP_EOL;
    } else {
        $failed++;
        echo 'FAIL: ' . $label . PHP_EOL;
    }
}

try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/setup-database.sql'));
} catch (Throwable $e) {
    fwrite(STDERR, 'SETUP FAILURE: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$sample = array(
    'student_name' => 'Jordan Lee',
    'student_email' => 'jordan@example.edu',
    'course' => 'IS 6465',
    'reason' => "Please help with PHP form validation & 'quoted' text.",
    'topics' => array('Exam review', 'Study plan'),
    'preferreddate' => (new DateTimeImmutable('+10 days'))->format('Y-m-d'),
    'preferredtime' => 'Morning'
);

$id = submitRequest($pdo, $sample);
$r = findRequest($pdo, $id);
check($r !== false && $r['status'] === 'Open', 'UC01: Create request with default Open status and JOIN retrieval');
check(decodeTopic($r['topic'])['topics'] === array('Exam review', 'Study plan'), 'UC01: Lab 4 checkbox topics preserved in JSON');
check(decodeTopic($r['topic'])['reason'] === $sample['reason'], 'UC01: Reason textarea with quotes preserved');

$id2 = submitRequest($pdo, $sample);
check((int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn() === 1, 'UC01: Duplicate student email reuses existing student');

check(count(listRequests($pdo)) === 2, 'UC02: No filters returns all saved requests');
check(count(listRequests($pdo, 'IS 6465', 'Open')) === 2, 'UC02: Combined course and status filter returns matches');

$bad = $sample;
$bad['reason'] = 'short';
$badRejected = false;
try {
    submitRequest($pdo, $bad);
} catch (InvalidArgumentException $e) {
    $badRejected = true;
}
check($badRejected, 'UC01: Invalid reason rejected');
check((int)$pdo->query('SELECT COUNT(*) FROM tutoring_requests')->fetchColumn() === 2, 'UC01: Invalid submission writes nothing');

$edit = $sample;
$edit['reason'] = 'Updated request reason for the exam.';
check(reviseRequest($pdo, $id, $edit), 'UC01: Edit Open request succeeds');
check(decodeTopic(findRequest($pdo, $id)['topic'])['reason'] === $edit['reason'], 'UC01: Updated reason persisted');

check(cancelOpenRequest($pdo, $id), 'UC01: Cancel Open request succeeds');
check(findRequest($pdo, $id)['status'] === 'Cancelled', 'UC01: Cancelled record retained with Cancelled status');
check(!cancelOpenRequest($pdo, $id), 'UC01: Second cancellation rejected');
check(!reviseRequest($pdo, $id, $edit), 'UC01: Editing Cancelled request rejected');
check(findRequest($pdo, $id2)['status'] === 'Open', 'UC01: Unrelated request unchanged');

$fkEnforced = false;
try {
    $pdo->exec("INSERT INTO tutoring_requests(student_id,course,topic,preferred_date,preferred_time) VALUES(999,'IS 6465','x','2030-01-01','Morning')");
} catch (PDOException $e) {
    $fkEnforced = true;
}
check($fkEnforced, 'Database: Foreign key constraint enforced');

check(count(listRequests($pdo, 'IS 6570', '')) === 0, 'UC02: Empty filter result handled');
check(count(listRequests($pdo, '', 'Cancelled')) === 1, 'UC02: Status-only filter returns cancelled record');

echo 'Results: ' . $passed . ' passed, ' . $failed . ' failed' . PHP_EOL;
exit($failed > 0 ? 1 : 0);
