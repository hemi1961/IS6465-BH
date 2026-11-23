<?php

function insertUser($pdo, $fullName, $email, $passwordHash, $role, $status = 'Active')
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, status)
         VALUES (:full_name, :email, :password_hash, :role, :status)'
    );
    $stmt->execute(array(
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
        'status' => $status
    ));

    return (int)$pdo->lastInsertId();
}

function findUserByEmail($pdo, $email)
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, password_hash, role, status, created_at
         FROM users
         WHERE email = :email'
    );
    $stmt->execute(array('email' => $email));
    return $stmt->fetch();
}

function findUserById($pdo, $userId)
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, role, status, created_at
         FROM users
         WHERE user_id = :user_id'
    );
    $stmt->execute(array('user_id' => (int)$userId));
    return $stmt->fetch();
}

function findUserAuthById($pdo, $userId)
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, password_hash, role, status, created_at
         FROM users
         WHERE user_id = :user_id'
    );
    $stmt->execute(array('user_id' => (int)$userId));
    return $stmt->fetch();
}

function searchUsers($pdo, $search = '', $role = '', $status = '')
{
    $sql = 'SELECT user_id, full_name, email, role, status, created_at
            FROM users
            WHERE 1=1';
    $params = array();

    if ($search !== '') {
        $sql .= ' AND (LOWER(full_name) LIKE LOWER(:search) OR LOWER(email) LIKE LOWER(:search))';
        $params['search'] = '%' . $search . '%';
    }
    if ($role !== '') {
        $sql .= ' AND role = :role';
        $params['role'] = $role;
    }
    if ($status !== '') {
        $sql .= ' AND status = :status';
        $params['status'] = $status;
    }

    $sql .= ' ORDER BY user_id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function updateUserProfileFields($pdo, $userId, $fullName, $email)
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET full_name = :full_name, email = :email
         WHERE user_id = :user_id'
    );
    $stmt->execute(array(
        'full_name' => $fullName,
        'email' => $email,
        'user_id' => (int)$userId
    ));

    return $stmt->rowCount() === 1;
}

function updateUserPasswordHash($pdo, $userId, $passwordHash)
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET password_hash = :password_hash
         WHERE user_id = :user_id'
    );
    $stmt->execute(array(
        'password_hash' => $passwordHash,
        'user_id' => (int)$userId
    ));

    return $stmt->rowCount() === 1;
}

function updateUserRoleById($pdo, $userId, $role)
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET role = :role
         WHERE user_id = :user_id'
    );
    $stmt->execute(array(
        'role' => $role,
        'user_id' => (int)$userId
    ));

    return $stmt->rowCount() === 1;
}

function updateUserStatusById($pdo, $userId, $status)
{
    $stmt = $pdo->prepare(
        'UPDATE users
         SET status = :status
         WHERE user_id = :user_id'
    );
    $stmt->execute(array(
        'status' => $status,
        'user_id' => (int)$userId
    ));

    return $stmt->rowCount() === 1;
}

function countActiveAdministrators($pdo)
{
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'Administrator' AND status = 'Active'"
    );
    return (int)$stmt->fetchColumn();
}

function countTutoringRequestsByUserId($pdo, $userId)
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE s.user_id = :user_id'
    );
    $stmt->execute(array('user_id' => (int)$userId));
    return (int)$stmt->fetchColumn();
}

function deleteUserById($pdo, $userId)
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
    $stmt->execute(array('user_id' => (int)$userId));
    return $stmt->rowCount() === 1;
}
