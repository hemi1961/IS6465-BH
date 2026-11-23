<?php

function insertTutoringRequest($pdo, $studentId, $data)
{
    $stmt = $pdo->prepare(
        'INSERT INTO tutoring_requests (student_id, course, topic, preferred_date, preferred_time)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute(array(
        (int)$studentId,
        $data['course'],
        $data['topic'],
        $data['preferred_date'],
        $data['preferred_time']
    ));
    return (int)$pdo->lastInsertId();
}

function findRequest($pdo, $id)
{
    $stmt = $pdo->prepare(
        'SELECT r.*, s.student_name, s.student_email, s.user_id
         FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE r.request_id = ?'
    );
    $stmt->execute(array((int)$id));
    return $stmt->fetch();
}

function findRequestForUser($pdo, $id, $userId)
{
    $stmt = $pdo->prepare(
        'SELECT r.*, s.student_name, s.student_email, s.user_id
         FROM tutoring_requests r
         JOIN students s ON r.student_id = s.student_id
         WHERE r.request_id = ? AND s.user_id = ?'
    );
    $stmt->execute(array((int)$id, (int)$userId));
    return $stmt->fetch();
}

function listRequests($pdo, $course = '', $status = '')
{
    $sql = 'SELECT r.*, s.student_name, s.student_email, s.user_id
            FROM tutoring_requests r
            JOIN students s ON r.student_id = s.student_id
            WHERE 1=1';
    $args = array();

    if ($course !== '') {
        $sql .= ' AND r.course = ?';
        $args[] = $course;
    }
    if ($status !== '') {
        $sql .= ' AND r.status = ?';
        $args[] = $status;
    }

    $sql .= ' ORDER BY r.request_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function listRequestsForUser($pdo, $userId, $course = '', $status = '')
{
    $sql = 'SELECT r.*, s.student_name, s.student_email, s.user_id
            FROM tutoring_requests r
            JOIN students s ON r.student_id = s.student_id
            WHERE s.user_id = ?';
    $args = array((int)$userId);

    if ($course !== '') {
        $sql .= ' AND r.course = ?';
        $args[] = $course;
    }
    if ($status !== '') {
        $sql .= ' AND r.status = ?';
        $args[] = $status;
    }

    $sql .= ' ORDER BY r.request_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function updateOpenRequest($pdo, $id, $data)
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET course = ?, topic = ?, preferred_date = ?, preferred_time = ?
         WHERE request_id = ? AND status = 'Open'"
    );
    $stmt->execute(array(
        $data['course'],
        $data['topic'],
        $data['preferred_date'],
        $data['preferred_time'],
        (int)$id
    ));
    return $stmt->rowCount() === 1;
}

function updateOpenRequestForUser($pdo, $id, $userId, $data)
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET course = ?, topic = ?, preferred_date = ?, preferred_time = ?
         WHERE request_id = ?
           AND status = 'Open'
           AND student_id IN (SELECT student_id FROM students WHERE user_id = ?)"
    );
    $stmt->execute(array(
        $data['course'],
        $data['topic'],
        $data['preferred_date'],
        $data['preferred_time'],
        (int)$id,
        (int)$userId
    ));
    return $stmt->rowCount() === 1;
}

function cancelOpenRequest($pdo, $id)
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET status = 'Cancelled'
         WHERE request_id = ? AND status = 'Open'"
    );
    $stmt->execute(array((int)$id));
    return $stmt->rowCount() === 1;
}

function cancelOpenRequestForUser($pdo, $id, $userId)
{
    $stmt = $pdo->prepare(
        "UPDATE tutoring_requests
         SET status = 'Cancelled'
         WHERE request_id = ?
           AND status = 'Open'
           AND student_id IN (SELECT student_id FROM students WHERE user_id = ?)"
    );
    $stmt->execute(array((int)$id, (int)$userId));
    return $stmt->rowCount() === 1;
}
