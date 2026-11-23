<?php
require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/repositories/userPDO.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';

/**
 * UM-01: Public self-registration for Student or Tutor accounts.
 * Creates an Active user with a secure one-way password hash.
 */
function registerUser($pdo, $input)
{
    $errors = array();
    $fullName = trim((string)(isset($input['full_name']) ? $input['full_name'] : ''));
    $email = normalizeEmail(isset($input['email']) ? $input['email'] : '');
    $password = (string)(isset($input['password']) ? $input['password'] : '');
    $confirmPassword = (string)(isset($input['confirm_password']) ? $input['confirm_password'] : '');
    $role = trim((string)(isset($input['role']) ? $input['role'] : ''));

    $msg = validateFullName($fullName);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    $msg = validateEmailAddress($email);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    $msg = validatePasswordStrength($password);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirmation password must match.';
    }

    $msg = validateRegistrationRole($role);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    if (findUserByEmail($pdo, $email) !== false) {
        throw new InvalidArgumentException('An account with that email address already exists.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        return insertUser($pdo, $fullName, $email, $passwordHash, $role, 'Active');
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
            throw new InvalidArgumentException('An account with that email address already exists.');
        }
        throw $e;
    }
}

/**
 * UM-09: Authenticate a registered user by email and password.
 * Returns sanitized user array (without password_hash) or false on failure.
 */
function authenticateUser($pdo, $email, $password)
{
    $normalizedEmail = normalizeEmail($email);
    $rawPassword = (string)$password;

    if ($normalizedEmail === '' || $rawPassword === '') {
        return false;
    }

    $user = findUserByEmail($pdo, $normalizedEmail);
    if (!$user || $user['status'] !== 'Active' || !password_verify($rawPassword, $user['password_hash'])) {
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($rawPassword, PASSWORD_DEFAULT);
        updateUserPasswordHash($pdo, (int)$user['user_id'], $newHash);
    }

    unset($user['password_hash']);
    return $user;
}

/**
 * UM-02: Retrieve own account profile (without password_hash).
 */
function getUserProfile($pdo, $userId)
{
    return findUserById($pdo, (int)$userId);
}

/**
 * UM-04: Update own full_name and email only.
 * Explicitly rejects attempts to modify role or status and synchronizes any
 * linked students record inside a transaction.
 */
function updateOwnProfile($pdo, $userId, $input)
{
    if (isset($input['role']) || isset($input['status'])) {
        throw new InvalidArgumentException('Role and account status cannot be modified through profile editing.');
    }

    $errors = array();
    $fullName = trim((string)(isset($input['full_name']) ? $input['full_name'] : ''));
    $email = normalizeEmail(isset($input['email']) ? $input['email'] : '');

    $msg = validateFullName($fullName);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    $msg = validateEmailAddress($email);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $existingUser = findUserByEmail($pdo, $email);
    if ($existingUser !== false && (int)$existingUser['user_id'] !== (int)$userId) {
        throw new InvalidArgumentException('That email address is already in use by another account.');
    }

    $existingStudent = findStudentByEmail($pdo, $email);
    if ($existingStudent !== false && (int)$existingStudent['user_id'] !== (int)$userId) {
        throw new InvalidArgumentException('That email address is already associated with another student record.');
    }

    $pdo->beginTransaction();
    try {
        $updated = updateUserProfileFields($pdo, (int)$userId, $fullName, $email);
        if (!$updated) {
            throw new RuntimeException('Unable to update user profile.');
        }

        $linkedStudent = findStudentByUserId($pdo, (int)$userId);
        if ($linkedStudent !== false) {
            updateStudentByUserId($pdo, (int)$userId, $fullName, $email);
        }

        $pdo->commit();
        return findUserById($pdo, (int)$userId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * UM-05: Change own password after verifying current password.
 */
function changeOwnPassword($pdo, $userId, $input)
{
    $currentPassword = (string)(isset($input['current_password']) ? $input['current_password'] : '');
    $newPassword = (string)(isset($input['new_password']) ? $input['new_password'] : '');
    $confirmPassword = (string)(isset($input['confirm_password']) ? $input['confirm_password'] : '');

    if ($currentPassword === '') {
        throw new InvalidArgumentException('Current password is required.');
    }

    $authUser = findUserAuthById($pdo, (int)$userId);
    if (!$authUser || !password_verify($currentPassword, $authUser['password_hash'])) {
        throw new InvalidArgumentException('Current password is incorrect.');
    }

    $errors = array();
    $msg = validatePasswordStrength($newPassword);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation password must match.';
    }

    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    return updateUserPasswordHash($pdo, (int)$userId, $newHash);
}

/**
 * Internal helper to enforce Administrator role at the service boundary.
 */
function assertAdministratorActor($actor)
{
    if (!hasAllowedRole($actor, array('Administrator'))) {
        throw new RuntimeException('Administrator authorization is required.');
    }
}

/**
 * UM-03: Search and filter user accounts (Administrator only).
 */
function listUsersForAdmin($pdo, $actor, $search = '', $role = '', $status = '')
{
    assertAdministratorActor($actor);

    $cleanSearch = trim((string)$search);
    $cleanRole = trim((string)$role);
    $cleanStatus = trim((string)$status);

    if ($cleanRole !== '' && validateAccountRole($cleanRole) !== '') {
        throw new InvalidArgumentException('Invalid role filter.');
    }
    if ($cleanStatus !== '' && validateAccountStatus($cleanStatus) !== '') {
        throw new InvalidArgumentException('Invalid status filter.');
    }

    return searchUsers($pdo, $cleanSearch, $cleanRole, $cleanStatus);
}

/**
 * UM-03: Inspect a single user account and related tutoring request count (Administrator only).
 */
function getUserDetailsForAdmin($pdo, $actor, $targetUserId)
{
    assertAdministratorActor($actor);

    $target = findUserById($pdo, (int)$targetUserId);
    if (!$target) {
        return false;
    }

    $target['related_request_count'] = countTutoringRequestsByUserId($pdo, (int)$targetUserId);
    return $target;
}

/**
 * UM-06: Update a user account's role (Administrator only).
 * Prevents removing or demoting the last active Administrator.
 */
function updateUserRoleForAdmin($pdo, $actor, $targetUserId, $newRole)
{
    assertAdministratorActor($actor);

    $cleanRole = trim((string)$newRole);
    $msg = validateAccountRole($cleanRole);
    if ($msg !== '') {
        throw new InvalidArgumentException($msg);
    }

    $pdo->beginTransaction();
    try {
        $target = findUserById($pdo, (int)$targetUserId);
        if (!$target) {
            throw new InvalidArgumentException('User account not found.');
        }

        if ($target['role'] === 'Administrator'
            && $target['status'] === 'Active'
            && $cleanRole !== 'Administrator'
            && countActiveAdministrators($pdo) <= 1
        ) {
            throw new InvalidArgumentException('Cannot change role: at least one active Administrator must remain.');
        }

        updateUserRoleById($pdo, (int)$targetUserId, $cleanRole);
        $pdo->commit();
        return findUserById($pdo, (int)$targetUserId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * UM-07: Activate or deactivate a user account (Administrator only).
 * Prevents deactivating the last active Administrator or the signed-in admin's own account.
 */
function updateUserStatusForAdmin($pdo, $actor, $targetUserId, $newStatus)
{
    assertAdministratorActor($actor);

    $cleanStatus = trim((string)$newStatus);
    $msg = validateAccountStatus($cleanStatus);
    if ($msg !== '') {
        throw new InvalidArgumentException($msg);
    }

    $pdo->beginTransaction();
    try {
        $target = findUserById($pdo, (int)$targetUserId);
        if (!$target) {
            throw new InvalidArgumentException('User account not found.');
        }

        if ($target['status'] === $cleanStatus) {
            throw new InvalidArgumentException('Account already has status ' . $cleanStatus . '.');
        }

        if ((int)$target['user_id'] === (int)$actor['user_id'] && $cleanStatus === 'Inactive') {
            throw new InvalidArgumentException('You cannot deactivate your own currently signed-in account.');
        }

        if ($target['role'] === 'Administrator'
            && $target['status'] === 'Active'
            && $cleanStatus === 'Inactive'
            && countActiveAdministrators($pdo) <= 1
        ) {
            throw new InvalidArgumentException('Cannot deactivate the last active Administrator account.');
        }

        updateUserStatusById($pdo, (int)$targetUserId, $cleanStatus);
        $pdo->commit();
        return findUserById($pdo, (int)$targetUserId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * UM-08: Delete a user account under retention policy (Administrator only).
 * Blocks deletion when related tutoring requests exist, when attempting self-deletion,
 * or when removing the last active Administrator.
 */
function deleteUserAccountForAdmin($pdo, $actor, $targetUserId, $confirmed = false)
{
    assertAdministratorActor($actor);

    if ($confirmed !== true) {
        throw new InvalidArgumentException('Account deletion must be explicitly confirmed.');
    }

    $pdo->beginTransaction();
    try {
        $target = findUserById($pdo, (int)$targetUserId);
        if (!$target) {
            throw new InvalidArgumentException('User account not found.');
        }

        if ((int)$target['user_id'] === (int)$actor['user_id']) {
            throw new InvalidArgumentException('You cannot delete your own currently signed-in account.');
        }

        if ($target['role'] === 'Administrator'
            && $target['status'] === 'Active'
            && countActiveAdministrators($pdo) <= 1
        ) {
            throw new InvalidArgumentException('Cannot delete the last active Administrator account.');
        }

        $relatedCount = countTutoringRequestsByUserId($pdo, (int)$targetUserId);
        if ($relatedCount > 0) {
            throw new InvalidArgumentException(
                'Deletion blocked by retention policy: this account has ' . $relatedCount
                . ' related tutoring request record(s). Deactivate the account instead.'
            );
        }

        deleteStudentByUserId($pdo, (int)$targetUserId);
        $deleted = deleteUserById($pdo, (int)$targetUserId);
        if (!$deleted) {
            throw new RuntimeException('Unable to delete user account.');
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
