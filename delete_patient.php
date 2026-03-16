<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');

verifyCsrfToken();

$patient_db_id = (int)($_POST['patient_db_id'] ?? 0);
if (!$patient_db_id) jsonResponse(false, 'Invalid patient ID.');

$db = getDB();
$stmt = $db->prepare('SELECT id, full_name FROM patients WHERE id = ?');
$stmt->execute([$patient_db_id]);
$patient = $stmt->fetch();

if (!$patient) jsonResponse(false, 'Patient Record Not Found');

$db->prepare('DELETE FROM patients WHERE id = ?')->execute([$patient_db_id]);

jsonResponse(true, "Patient record for \"{$patient['full_name']}\" has been permanently deleted.");