<?php

function findStudentByEmail($pdo, $email)
{
    $stmt = $pdo->prepare(
        'SELECT student_id, student_name, student_email, user_id
         FROM students
         WHERE student_email = ?'
    );
    $stmt->execute(array($email));
    return $stmt->fetch();
}

function findStudentByUserId($pdo, $userId)
{
    $stmt = $pdo->prepare(
        'SELECT student_id, student_name, student_email, user_id
         FROM students
         WHERE user_id = ?'
    );
    $stmt->execute(array((int)$userId));
    return $stmt->fetch();
}

function insertStudentIfAbsent($pdo, $name, $email, $userId = null)
{
    if ($userId !== null) {
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO students (student_name, student_email, user_id)
             VALUES (?, ?, ?)'
        );
        $stmt->execute(array($name, $email, (int)$userId));
    } else {
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO students (student_name, student_email)
             VALUES (?, ?)'
        );
        $stmt->execute(array($name, $email));
    }
}

function linkStudentToUser($pdo, $studentId, $userId, $name, $email)
{
    $stmt = $pdo->prepare(
        'UPDATE students
         SET user_id = ?, student_name = ?, student_email = ?
         WHERE student_id = ?'
    );
    $stmt->execute(array((int)$userId, $name, $email, (int)$studentId));
    return $stmt->rowCount() === 1;
}

function updateStudentByUserId($pdo, $userId, $name, $email)
{
    $stmt = $pdo->prepare(
        'UPDATE students
         SET student_name = ?, student_email = ?
         WHERE user_id = ?'
    );
    $stmt->execute(array($name, $email, (int)$userId));
    return $stmt->rowCount() === 1;
}

function deleteStudentByUserId($pdo, $userId)
{
    $stmt = $pdo->prepare('DELETE FROM students WHERE user_id = ?');
    $stmt->execute(array((int)$userId));
    return $stmt->rowCount();
}
