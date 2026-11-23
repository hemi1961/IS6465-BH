<?php
/**
 * Automated PHP CLI Integration & Security Test Harness for Lab 7
 * Covers UM-01 through UM-10, authentication, CSRF, session timeout,
 * role-based access control, and SQL ownership enforcement.
 *
 * Run from the lab7 directory:
 *   php tests/user-management-tests.php
 */
ob_start();
require_once dirname(__DIR__) . '/services/userService.php';
require_once dirname(__DIR__) . '/services/requestService.php';

$passed = 0;
$failed = 0;

function check($condition, $label)
{
    global $passed, $failed;

    if ($condition) {
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

    $schemaSql = file_get_contents(dirname(__DIR__) . '/database/setup-database.sql');
    if ($schemaSql === false) {
        throw new RuntimeException('Unable to read database/setup-database.sql');
    }
    $pdo->exec($schemaSql);
} catch (Throwable $e) {
    fwrite(STDERR, 'SETUP FAILURE: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Seed one Administrator directly for admin test scenarios
$adminId = insertUser(
    $pdo,
    'Alex Morgan',
    'admin@example.edu',
    password_hash('AdminPass123!', PASSWORD_DEFAULT),
    'Administrator',
    'Active'
);
$adminActor = findUserById($pdo, $adminId);

// ------------------------------------------------------------------
// UM-01: Create User Account (Student / Tutor self-registration)
// ------------------------------------------------------------------
$student1Id = registerUser($pdo, array(
    'full_name' => 'Priya Patel',
    'email' => 'Priya.Patel@Example.EDU',
    'password' => 'StudentPass123!',
    'confirm_password' => 'StudentPass123!',
    'role' => 'Student'
));
$student1Auth = findUserAuthById($pdo, $student1Id);
check(
    $student1Auth !== false
    && $student1Auth['email'] === 'priya.patel@example.edu'
    && $student1Auth['role'] === 'Student'
    && $student1Auth['status'] === 'Active',
    'UM-01: Valid Student registration creates Active user with normalized email'
);
check(
    $student1Auth['password_hash'] !== 'StudentPass123!'
    && password_verify('StudentPass123!', $student1Auth['password_hash']),
    'UM-01: Password stored as one-way password_hash, not plaintext'
);

$tutor1Id = registerUser($pdo, array(
    'full_name' => 'Taylor Lee',
    'email' => 'taylor.lee@example.edu',
    'password' => 'TutorPass123!',
    'confirm_password' => 'TutorPass123!',
    'role' => 'Tutor'
));
$tutor1 = findUserById($pdo, $tutor1Id);
check(
    $tutor1 !== false && $tutor1['role'] === 'Tutor' && $tutor1['status'] === 'Active',
    'UM-01: Valid Tutor registration creates Active user'
);

$student2Id = registerUser($pdo, array(
    'full_name' => 'Jordan Kim',
    'email' => 'jordan.kim@example.edu',
    'password' => 'StudentPass123!',
    'confirm_password' => 'StudentPass123!',
    'role' => 'Student'
));
$student2 = findUserById($pdo, $student2Id);

// Duplicate email rejected
$dupRejected = false;
try {
    registerUser($pdo, array(
        'full_name' => 'Duplicate Priya',
        'email' => 'priya.patel@example.edu',
        'password' => 'AnotherPass123!',
        'confirm_password' => 'AnotherPass123!',
        'role' => 'Student'
    ));
} catch (InvalidArgumentException $e) {
    $dupRejected = true;
}
check($dupRejected, 'UM-01: Duplicate email registration rejected');

// Public registration cannot grant Administrator role
$adminRegRejected = false;
try {
    registerUser($pdo, array(
        'full_name' => 'Sneaky Admin',
        'email' => 'sneaky@example.edu',
        'password' => 'SneakyPass123!',
        'confirm_password' => 'SneakyPass123!',
        'role' => 'Administrator'
    ));
} catch (InvalidArgumentException $e) {
    $adminRegRejected = true;
}
check(
    $adminRegRejected && findUserByEmail($pdo, 'sneaky@example.edu') === false,
    'UM-01: Public registration attempting Administrator role is denied with no DB write'
);

// Password confirmation mismatch rejected
$mismatchRejected = false;
try {
    registerUser($pdo, array(
        'full_name' => 'Mismatch User',
        'email' => 'mismatch@example.edu',
        'password' => 'StrongPass123!',
        'confirm_password' => 'DifferentPass123!',
        'role' => 'Student'
    ));
} catch (InvalidArgumentException $e) {
    $mismatchRejected = true;
}
check($mismatchRejected, 'UM-01: Registration with mismatched passwords rejected');

// Short password rejected
$shortPwdRejected = false;
try {
    registerUser($pdo, array(
        'full_name' => 'Short Pass',
        'email' => 'short@example.edu',
        'password' => 'short',
        'confirm_password' => 'short',
        'role' => 'Tutor'
    ));
} catch (InvalidArgumentException $e) {
    $shortPwdRejected = true;
}
check($shortPwdRejected, 'UM-01: Registration with password under 8 characters rejected');

// ------------------------------------------------------------------
// UM-02: View Own Account
// ------------------------------------------------------------------
$student1Profile = getUserProfile($pdo, $student1Id);
check(
    $student1Profile !== false
    && $student1Profile['full_name'] === 'Priya Patel'
    && $student1Profile['email'] === 'priya.patel@example.edu'
    && $student1Profile['role'] === 'Student'
    && $student1Profile['status'] === 'Active'
    && !isset($student1Profile['password_hash']),
    'UM-02: View own profile returns permitted fields and omits password_hash'
);

// ------------------------------------------------------------------
// UM-03: View and Search User Accounts (Administrator)
// ------------------------------------------------------------------
$allUsers = listUsersForAdmin($pdo, $adminActor);
check(count($allUsers) === 4, 'UM-03: Admin lists all 4 accounts without filters');

$searchByName = listUsersForAdmin($pdo, $adminActor, 'Priya');
check(
    count($searchByName) === 1 && (int)$searchByName[0]['user_id'] === $student1Id,
    'UM-03: Admin search by name returns matching account'
);

$filterByRole = listUsersForAdmin($pdo, $adminActor, '', 'Tutor', 'Active');
check(
    count($filterByRole) === 1 && (int)$filterByRole[0]['user_id'] === $tutor1Id,
    'UM-03: Admin combined role and status filter returns matching account'
);

$noMatches = listUsersForAdmin($pdo, $adminActor, 'nonexistent-user-xyz');
check(count($noMatches) === 0, 'UM-03: Admin search with no matches returns empty list');

$nonAdminSearchDenied = false;
try {
    listUsersForAdmin($pdo, $student1Profile, '');
} catch (RuntimeException $e) {
    $nonAdminSearchDenied = true;
}
check($nonAdminSearchDenied, 'UM-03: Non-administrator denied from viewing/searching user accounts');

// ------------------------------------------------------------------
// Protected Tutoring Request Creation + Ownership Setup
// ------------------------------------------------------------------
$futureDate = (new DateTimeImmutable('+14 days'))->format('Y-m-d');
$student1ReqId = submitRequestForStudent($pdo, $student1Profile, array(
    'course' => 'IS 6465',
    'reason' => 'Reviewing sessions, cookies, and ownership checks in SQL.',
    'topics' => array('Exam review', 'Practice problems'),
    'preferreddate' => $futureDate,
    'preferredtime' => 'Afternoon'
));
$student2ReqId = submitRequestForStudent($pdo, $student2, array(
    'course' => 'IS 6495',
    'reason' => 'Need practice problems on SQL joins and normalization.',
    'topics' => array('Practice problems'),
    'preferreddate' => $futureDate,
    'preferredtime' => 'Morning'
));

check(
    count(listRequestsForActor($pdo, $student1Profile)) === 1,
    'Ownership: Student 1 sees only own tutoring request in list'
);
check(
    findRequestForActor($pdo, $student1Profile, $student2ReqId) === false,
    'Ownership: Student 1 cannot view Student 2 tutoring request'
);
check(
    reviseRequestForActor($pdo, $student1Profile, $student2ReqId, array(
        'course' => 'IS 6570',
        'reason' => 'Attempting unauthorized edit on another student request.',
        'topics' => array('Study plan'),
        'preferreddate' => $futureDate,
        'preferredtime' => 'Evening'
    )) === false,
    'Ownership: Student 1 cannot edit Student 2 tutoring request'
);
check(
    cancelRequestForActor($pdo, $student1Profile, $student2ReqId) === false
    && findRequest($pdo, $student2ReqId)['status'] === 'Open',
    'Ownership: Student 1 cannot cancel Student 2 tutoring request'
);

// Tutor denied all tutoring request workflows
$tutorSubmitDenied = false;
try {
    submitRequestForStudent($pdo, $tutor1, array(
        'course' => 'IS 6465',
        'reason' => 'Tutor attempting to submit student request.',
        'topics' => array('Study plan'),
        'preferreddate' => $futureDate,
        'preferredtime' => 'Morning'
    ));
} catch (RuntimeException $e) {
    $tutorSubmitDenied = true;
}
check($tutorSubmitDenied, 'Authorization: Tutor denied from submitting tutoring requests');

$tutorViewDenied = false;
try {
    listRequestsForActor($pdo, $tutor1);
} catch (RuntimeException $e) {
    $tutorViewDenied = true;
}
check($tutorViewDenied, 'Authorization: Tutor denied from viewing tutoring requests');

// Administrator cannot submit a request, but can view all requests and edit any Open request
$adminSubmitDenied = false;
try {
    submitRequestForStudent($pdo, $adminActor, array(
        'course' => 'IS 6465',
        'reason' => 'Admin attempting to submit student request.',
        'topics' => array('Study plan'),
        'preferreddate' => $futureDate,
        'preferredtime' => 'Morning'
    ));
} catch (RuntimeException $e) {
    $adminSubmitDenied = true;
}
check($adminSubmitDenied, 'Authorization: Administrator denied from submitting student tutoring requests');
check(
    count(listRequestsForActor($pdo, $adminActor)) === 2,
    'Authorization: Administrator views all tutoring requests across students'
);

// ------------------------------------------------------------------
// UM-04: Update Own Account (including linked students sync & role tamper denial)
// ------------------------------------------------------------------
$updatedProfile = updateOwnProfile($pdo, $student1Id, array(
    'full_name' => 'Priya N. Patel',
    'email' => 'priya.n.patel@example.edu'
));
$syncedStudent = findStudentByUserId($pdo, $student1Id);
check(
    $updatedProfile['full_name'] === 'Priya N. Patel'
    && $updatedProfile['email'] === 'priya.n.patel@example.edu'
    && $syncedStudent !== false
    && $syncedStudent['student_name'] === 'Priya N. Patel'
    && $syncedStudent['student_email'] === 'priya.n.patel@example.edu',
    'UM-04: Profile update saves name/email and synchronizes linked students record'
);

// Role escalation via profile edit denied
$roleTamperDenied = false;
try {
    updateOwnProfile($pdo, $student1Id, array(
        'full_name' => 'Priya N. Patel',
        'email' => 'priya.n.patel@example.edu',
        'role' => 'Administrator'
    ));
} catch (InvalidArgumentException $e) {
    $roleTamperDenied = true;
}
check(
    $roleTamperDenied && findUserById($pdo, $student1Id)['role'] === 'Student',
    'UM-04: Role modification via profile edit is denied and role remains Student'
);

// Duplicate email rejected on profile update
$dupUpdateDenied = false;
try {
    updateOwnProfile($pdo, $student1Id, array(
        'full_name' => 'Priya N. Patel',
        'email' => 'jordan.kim@example.edu'
    ));
} catch (InvalidArgumentException $e) {
    $dupUpdateDenied = true;
}
check($dupUpdateDenied, 'UM-04: Profile update with another user email is rejected');

// ------------------------------------------------------------------
// UM-05: Change Password
// ------------------------------------------------------------------
$wrongCurrentDenied = false;
try {
    changeOwnPassword($pdo, $student1Id, array(
        'current_password' => 'WrongPass999!',
        'new_password' => 'NewSecurePass456!',
        'confirm_password' => 'NewSecurePass456!'
    ));
} catch (InvalidArgumentException $e) {
    $wrongCurrentDenied = true;
}
check($wrongCurrentDenied, 'UM-05: Password change rejected when current password is wrong');

$pwdChanged = changeOwnPassword($pdo, $student1Id, array(
    'current_password' => 'StudentPass123!',
    'new_password' => 'NewSecurePass456!',
    'confirm_password' => 'NewSecurePass456!'
));
check(
    $pwdChanged
    && authenticateUser($pdo, 'priya.n.patel@example.edu', 'StudentPass123!') === false
    && authenticateUser($pdo, 'priya.n.patel@example.edu', 'NewSecurePass456!') !== false,
    'UM-05: New password succeeds on login and old password fails'
);

// ------------------------------------------------------------------
// UM-06: Update User Role (Administrator)
// ------------------------------------------------------------------
$nonAdminRoleDenied = false;
try {
    updateUserRoleForAdmin($pdo, $student1Profile, $tutor1Id, 'Administrator');
} catch (RuntimeException $e) {
    $nonAdminRoleDenied = true;
}
check($nonAdminRoleDenied, 'UM-06: Unauthorized non-admin role update denied');

// Last active administrator cannot be demoted
$lastAdminDemoteDenied = false;
try {
    updateUserRoleForAdmin($pdo, $adminActor, $adminId, 'Tutor');
} catch (InvalidArgumentException $e) {
    $lastAdminDemoteDenied = true;
}
check(
    $lastAdminDemoteDenied && findUserById($pdo, $adminId)['role'] === 'Administrator',
    'UM-06: Demoting the last active Administrator is prevented'
);

// Promote Tutor to Administrator, then demote back to Tutor
$promoted = updateUserRoleForAdmin($pdo, $adminActor, $tutor1Id, 'Administrator');
check($promoted['role'] === 'Administrator', 'UM-06: Admin promotes Tutor to Administrator');
$demoted = updateUserRoleForAdmin($pdo, $adminActor, $tutor1Id, 'Tutor');
check($demoted['role'] === 'Tutor', 'UM-06: Admin changes role back to Tutor');

// ------------------------------------------------------------------
// UM-07: Activate or Deactivate Account (Administrator)
// ------------------------------------------------------------------
$deactivated = updateUserStatusForAdmin($pdo, $adminActor, $student1Id, 'Inactive');
check(
    $deactivated['status'] === 'Inactive'
    && authenticateUser($pdo, 'priya.n.patel@example.edu', 'NewSecurePass456!') === false,
    'UM-07: Deactivated user cannot log in'
);

$reactivated = updateUserStatusForAdmin($pdo, $adminActor, $student1Id, 'Active');
check(
    $reactivated['status'] === 'Active'
    && authenticateUser($pdo, 'priya.n.patel@example.edu', 'NewSecurePass456!') !== false,
    'UM-07: Reactivated user can log in again'
);

$lastAdminDeactivateDenied = false;
try {
    updateUserStatusForAdmin($pdo, $adminActor, $adminId, 'Inactive');
} catch (InvalidArgumentException $e) {
    $lastAdminDeactivateDenied = true;
}
check($lastAdminDeactivateDenied, 'UM-07: Deactivating own/last active Administrator is prevented');

// ------------------------------------------------------------------
// UM-08: Delete User Account (Administrator + Retention Policy)
// ------------------------------------------------------------------
$unconfirmedDeleteBlocked = false;
try {
    deleteUserAccountForAdmin($pdo, $adminActor, $tutor1Id, false);
} catch (InvalidArgumentException $e) {
    $unconfirmedDeleteBlocked = true;
}
check($unconfirmedDeleteBlocked, 'UM-08: Unconfirmed deletion is rejected');

// Student with related tutoring request MUST be blocked by retention policy
$retentionBlocked = false;
try {
    deleteUserAccountForAdmin($pdo, $adminActor, $student1Id, true);
} catch (InvalidArgumentException $e) {
    $retentionBlocked = true;
}
check(
    $retentionBlocked
    && findUserById($pdo, $student1Id) !== false
    && findRequest($pdo, $student1ReqId) !== false,
    'UM-08: Deletion of account with related tutoring requests is blocked and records retained'
);

// Tutor with 0 related tutoring requests CAN be deleted when confirmed
$tutorDeleted = deleteUserAccountForAdmin($pdo, $adminActor, $tutor1Id, true);
check(
    $tutorDeleted && findUserById($pdo, $tutor1Id) === false,
    'UM-08: Confirmed deletion of account with 0 related tutoring requests succeeds'
);

// ------------------------------------------------------------------
// UM-09 & UM-10: Authentication, Session Timeout, and CSRF Helpers
// ------------------------------------------------------------------
$loginOk = authenticateUser($pdo, 'ADMIN@EXAMPLE.EDU', 'AdminPass123!');
check(
    $loginOk !== false && (int)$loginOk['user_id'] === $adminId && !isset($loginOk['password_hash']),
    'UM-09: Valid login authenticates case-insensitively and omits password_hash'
);
check(
    authenticateUser($pdo, 'admin@example.edu', 'WrongPassword!') === false,
    'UM-09: Invalid password fails authentication'
);
check(
    authenticateUser($pdo, 'nobody@example.edu', 'AdminPass123!') === false,
    'UM-09: Unknown email fails authentication'
);

// CSRF helper verification
$sampleToken = bin2hex(random_bytes(32));
check(isValidCsrfToken($sampleToken, $sampleToken), 'Security: Valid CSRF token accepted');
check(!isValidCsrfToken($sampleToken, 'wrong-token'), 'Security: Tampered CSRF token rejected');
check(!isValidCsrfToken($sampleToken, ''), 'Security: Empty CSRF token rejected');

// 20-minute inactivity timeout verification
$now = 1700000000;
check(!isSessionExpired($now - 1200, $now, 1200), 'Security: Session active at exactly 1200 seconds');
check(isSessionExpired($now - 1201, $now, 1200), 'Security: Session expires after 1200 seconds inactivity');

// UM-10: Session login/logout lifecycle in CLI
loginUserSession($loginOk);
check(
    isset($_SESSION['user_id']) && $_SESSION['user_id'] === $adminId && $_SESSION['role'] === 'Administrator',
    'UM-09: loginUserSession stores minimal trusted session state'
);
logoutSession();
check(
    empty($_SESSION) && !isset($_SESSION['user_id']),
    'UM-10: logoutSession clears session state cleanly'
);

echo 'Results: ' . $passed . ' passed, ' . $failed . ' failed' . PHP_EOL;
ob_end_flush();
exit($failed > 0 ? 1 : 0);
