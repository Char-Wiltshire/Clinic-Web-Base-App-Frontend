<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();

$file_id = (int)($_GET['id'] ?? 0);
$download = isset($_GET['download']);

if (!$file_id) { http_response_code(400); exit('Invalid file ID.');}

$db = getDB();
$stmt = $db->prepare('SELECT file_name, file_type, file_data FROM medical_files WHERE id = ?');
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if(!$file) { http_response_code(404); exit('File Not Found');}

header('Content-Type: ' . $file['file_type']);
header('Content-Length: ' . strlen($file['file_data']));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . addslashes($file['file_name']) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
echo $file['file_data'];
exit;
