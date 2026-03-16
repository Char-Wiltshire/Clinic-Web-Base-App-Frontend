<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header ('Content-Type: application/json');

$db = getDB();
$stmt = $db->prepare('SELECT note, updated_at FROM user_notes WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch();

jsonResponse(true, 'OK', [
    'note' => $row ? $row['note'] : '',
    'updated_at' => $row ? date('M j, Y g:i A', strtotime($row['updated_at'])) : null,
]);