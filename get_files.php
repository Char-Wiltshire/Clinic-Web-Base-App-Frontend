<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header('Content-Type: application/json');

$patient_db_id = (int)($_GET['patient_id'] ?? 0);
if (!$patient_db_id) jsonResponse(false, 'Invalid patient ID.');

$db = getDB();
$stmt = $db->prepare(
    'SELECT f.id, f.file_name, f.file_type, f.uploaded_at, u.full_name AS uploaded_by
    FROM medical_files f
    JOIN users u ON u.id = f.uploaded_by
    WHERE f.patient_id = ?
    ORDER BY f.uploaded_at DESC'
);

$stmt->execute([$patient_db_id]);
$files = $stmt->fetchAll();

foreach ($files as &$f) {
    $f['uploaded_at'] = date('Y-m-d H:i', strtotime($f['uploaded_at']));
    $f['view_url'] = "../api/view_file.php?id={$f['id']}";
    $f['download_url'] = "../api/view_file.php?id={$f['id']}&download=1";
}

jsonResponse(true, 'OK', ['files'=>$files]);