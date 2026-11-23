<?php
require_once __DIR__ . '/validation.php';
$titleText = isset($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' - Peer Tutoring Hub'
    : 'Peer Tutoring Hub';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <title><?= escapeHtml($titleText) ?></title>
</head>
<body>
