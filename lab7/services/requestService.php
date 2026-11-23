<?php
require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/repositories/studentPDO.php';
require_once dirname(__DIR__) . '/repositories/requestPDO.php';

// Week 4 form fields map to the approved schema without adding request columns.
// topic contains JSON {reason,topics}; preferred_time stores Morning/Afternoon/Evening.
function decodeTopic($topic)
{
    $v = json_decode((string)$topic, true);
    return (is_array($v) && isset($v['reason']) && isset($v['topics']) && is_array($v['topics']))
        ? $v
        : array('reason' => (string)$topic, 'topics' => array());
}

function validateSubmission($input, $requireStudent = true)
{
    $errors = array();
    $courses = array('IS 6465', 'IS 6495', 'IS 6570');

    $name = trim((string)(isset($input['student_name']) ? $input['student_name'] : ''));
    $email = normalizeEmail(isset($input['student_email']) ? $input['student_email'] : '');
    $course = trim((string)(isset($input['course']) ? $input['course'] : ''));
    $reason = trim((string)(isset($input['reason']) ? $input['reason'] : ''));
    $date = (string)(isset($input['preferreddate']) ? $input['preferreddate'] : '');
    $time = (string)(isset($input['preferredtime']) ? $input['preferredtime'] : '');
    $topics = isset($input['topics']) ? $input['topics'] : array();

    if (!is_array($topics)) {
        $errors[] = 'Topics must be a list.';
        $topics = array();
    }
    if ($requireStudent && $name === '') {
        $errors[] = 'Student name is required.';
    }
    if ($requireStudent && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid student email required.';
    }
    if (!in_array($course, $courses, true)) {
        $errors[] = 'Select a valid course.';
    }
    $msg = validatePreferredTime($time);
    if ($msg !== '') {
        $errors[] = $msg;
    }
    $msg = validateTopics($topics);
    if ($msg !== '') {
        $errors[] = $msg;
    }
    $msg = validateReason($reason);
    if ($msg !== '') {
        $errors[] = $msg;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date || $dt < new DateTimeImmutable('today')) {
        $errors[] = 'Choose a valid date today or later.';
    }

    $payload = array(
        'student_name' => $name,
        'student_email' => $email,
        'course' => $course,
        'topic' => json_encode(array('reason' => $reason, 'topics' => array_values($topics))),
        'preferred_date' => $date,
        'preferred_time' => $time
    );

    return array($errors, $payload);
}

/**
 * UC01: Create a tutoring request linked to a student record in a transaction.
 * When $userId is supplied (Lab 7 authenticated Student workflow), links the
 * student record through students.user_id.
 */
function submitRequest($pdo, $input, $userId = null)
{
    list($errors, $data) = validateSubmission($input, true);
    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $pdo->beginTransaction();
    try {
        $student = false;

        if ($userId !== null) {
            $student = findStudentByUserId($pdo, (int)$userId);
            if ($student === false) {
                $existingByEmail = findStudentByEmail($pdo, $data['student_email']);
                if ($existingByEmail !== false && $existingByEmail['user_id'] === null) {
                    linkStudentToUser(
                        $pdo,
                        (int)$existingByEmail['student_id'],
                        (int)$userId,
                        $data['student_name'],
                        $data['student_email']
                    );
                    $student = findStudentByUserId($pdo, (int)$userId);
                } else {
                    insertStudentIfAbsent($pdo, $data['student_name'], $data['student_email'], (int)$userId);
                    $student = findStudentByUserId($pdo, (int)$userId);
                }
            }
        } else {
            insertStudentIfAbsent($pdo, $data['student_name'], $data['student_email']);
            $student = findStudentByEmail($pdo, $data['student_email']);
        }

        if (!$student) {
            throw new RuntimeException('Student lookup failed');
        }

        $id = insertTutoringRequest($pdo, (int)$student['student_id'], $data);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Lab 7 protected Student submission workflow.
 */
function submitRequestForStudent($pdo, $actor, $input)
{
    if (!hasAllowedRole($actor, array('Student'))) {
        throw new RuntimeException('Only Student accounts may submit tutoring requests.');
    }

    $boundInput = is_array($input) ? $input : array();
    $boundInput['student_name'] = $actor['full_name'];
    $boundInput['student_email'] = $actor['email'];

    return submitRequest($pdo, $boundInput, (int)$actor['user_id']);
}

function reviseRequest($pdo, $id, $input)
{
    list($errors, $data) = validateSubmission($input, false);
    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }
    return updateOpenRequest($pdo, $id, $data);
}

function listRequestsForActor($pdo, $actor, $course = '', $status = '')
{
    if (hasAllowedRole($actor, array('Administrator'))) {
        return listRequests($pdo, $course, $status);
    }
    if (hasAllowedRole($actor, array('Student'))) {
        return listRequestsForUser($pdo, (int)$actor['user_id'], $course, $status);
    }
    throw new RuntimeException('You are not authorized to view tutoring requests.');
}

function findRequestForActor($pdo, $actor, $requestId)
{
    if (hasAllowedRole($actor, array('Administrator'))) {
        return findRequest($pdo, (int)$requestId);
    }
    if (hasAllowedRole($actor, array('Student'))) {
        return findRequestForUser($pdo, (int)$requestId, (int)$actor['user_id']);
    }
    throw new RuntimeException('You are not authorized to view tutoring requests.');
}

function reviseRequestForActor($pdo, $actor, $requestId, $input)
{
    list($errors, $data) = validateSubmission($input, false);
    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    if (hasAllowedRole($actor, array('Administrator'))) {
        return updateOpenRequest($pdo, (int)$requestId, $data);
    }
    if (hasAllowedRole($actor, array('Student'))) {
        return updateOpenRequestForUser($pdo, (int)$requestId, (int)$actor['user_id'], $data);
    }
    throw new RuntimeException('You are not authorized to update tutoring requests.');
}

function cancelRequestForActor($pdo, $actor, $requestId)
{
    if (hasAllowedRole($actor, array('Administrator'))) {
        return cancelOpenRequest($pdo, (int)$requestId);
    }
    if (hasAllowedRole($actor, array('Student'))) {
        return cancelOpenRequestForUser($pdo, (int)$requestId, (int)$actor['user_id']);
    }
    throw new RuntimeException('You are not authorized to cancel tutoring requests.');
}
