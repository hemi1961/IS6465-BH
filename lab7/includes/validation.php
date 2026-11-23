<?php

if (!function_exists('escapeHtml')) {
    function escapeHtml($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function validatePreferredTime($preferredTime)
{
    $allowedTimes = array('Morning', 'Afternoon', 'Evening');
    if (!in_array($preferredTime, $allowedTimes, true)) {
        return 'Choose a valid preferred time.';
    }
    return '';
}

function validateTopics($topics)
{
    $allowedTopics = array('Exam review', 'Practice problems', 'Study plan');

    if (!is_array($topics) || count($topics) === 0) {
        return 'Select at least one valid topic.';
    }

    foreach ($topics as $topic) {
        if (!in_array($topic, $allowedTopics, true)) {
            return 'Select at least one valid topic.';
        }
    }

    return '';
}

function validateReason($reason)
{
    $length = mb_strlen(trim((string)$reason));

    if ($length < 10 || $length > 500) {
        return 'Describe your request using 10 to 500 characters.';
    }

    return '';
}

function normalizeEmail($email)
{
    return strtolower(trim((string)$email));
}

function validateFullName($fullName)
{
    $trimmed = trim((string)$fullName);
    $length = mb_strlen($trimmed);

    if ($length < 2 || $length > 120) {
        return 'Full name must be between 2 and 120 characters.';
    }

    return '';
}

function validateEmailAddress($email)
{
    $normalized = normalizeEmail($email);

    if ($normalized === '' || mb_strlen($normalized) > 254 || !filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address.';
    }

    return '';
}

function validatePasswordStrength($password)
{
    $pwd = (string)$password;
    $length = strlen($pwd);

    if ($length < 8) {
        return 'Password must be at least 8 characters long.';
    }

    if ($length > 72) {
        return 'Password must not exceed 72 characters.';
    }

    return '';
}

function validateRegistrationRole($role)
{
    $allowedPublicRoles = array('Student', 'Tutor');
    if (!in_array($role, $allowedPublicRoles, true)) {
        return 'Public registration is limited to Student or Tutor accounts.';
    }
    return '';
}

function validateAccountRole($role)
{
    $allowedRoles = array('Student', 'Tutor', 'Administrator');
    if (!in_array($role, $allowedRoles, true)) {
        return 'Choose a valid account role.';
    }
    return '';
}

function validateAccountStatus($status)
{
    $allowedStatuses = array('Active', 'Inactive');
    if (!in_array($status, $allowedStatuses, true)) {
        return 'Choose a valid account status.';
    }
    return '';
}
