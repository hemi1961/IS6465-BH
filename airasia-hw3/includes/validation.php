<?php
function validateCardName($name) { return !empty(trim($name)) && strlen($name) <= 100; }
function validateCardType($type) { return in_array($type, ['Travel', 'Dining', 'Retail']); }
function validateCardValue($value) { return is_numeric($value) && $value > 0; }
function validatePoints($points) { return filter_var($points, FILTER_VALIDATE_INT) !== false && $points > 0; }
function validateId($id) { return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0; }
?>