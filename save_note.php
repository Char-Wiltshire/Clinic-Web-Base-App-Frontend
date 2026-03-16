<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header ('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid Request Method');

verifyCsrfToken();

$note = $_POST['note'] ?? '';
$db = getDB();

$stmt = $db->prepare('SELECT id FROM user_notes WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$existing = $stmt->fetch();

if($existing) {
    $db->prepare('UPDATE user_notes SET note = ?, updated_at = NOW() WHERE user_id = ?')
    ->execute([$note, $_SESSION['user_id']]);
} else {
    $db->prepare('INSERT INTO user_notes (user_id, note) VALUES (?, ?)')
    ->execute([$_SESSION['user_id'], $note] );
}

jsonResponse(true, 'Note Saved', ['saved_at' => date('g:i A')]);