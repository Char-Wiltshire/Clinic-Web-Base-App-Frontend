<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header ('Content-Type: application/json');

$patient_db_id = (int) ($_GET['id'] ?? 0);
if (!$patient_db_id) jsonResponse(false, 'Invalid Patient ID.');

$db=getDB();
$stmt = $db->prepare(
    'SELECT id, patient_id, full_name, gender, age, height_cm, weight_kg,
    blood_pressure, blood_type, department, medical_history
    FROM patients WHERE id = ?'
);
$stmt->execute([$patient_db_id]);
$patient = $stmt->fetch();

if (!$patient) jsonResponse(false, 'Patient not Found.');
$fstmt = $db->prepare (
    'SELECT id, file_name, file_type, uploaded_at
    FROM medical_files WHERE patient_id = ? ORDER BY uploaded_at DESC'
);
$fstmt->execute([$patient_db_id]);
$files = $fstmt->fetchAll();

foreach ($files as &$f) {
    $f['uploaded_at'] = date('Y-m-d H:i', strtotime($f['uploaded_at']));
    $f['view_url'] = "../api/view_file.php?id={$f['id']}";
    $f['download_url'] = "../api/view_file.php?id={$f['id']}&download=1";
}

jsonResponse(true, 'OK', ['patient'=> $patient, 'files'=> $files]);