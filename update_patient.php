<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header ('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request');

verifyCsrfToken();

$patient_db_id = (int)($_POST['patient_db_id'] ?? 0);
$patient_id = trim($_POST['patient_id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$age = trim($_POST['age'] ?? '');
$height = trim($_POST['height'] ?? '');
$weight = trim($_POST['weight'] ?? '');
$blood_pressure = trim($_POST['blood_pressure'] ?? '');
$blood_type = trim($_POST['blood_type'] ?? 'Unknown');
$department = trim($_POST['department'] ??  '');
$medical_history = trim($_POST['medical_history'] ?? '');

if (!$patient_db_id) jsonResponse(false, 'Invalid Patient ID');
if (!$patient_id || !$full_name || !$gender || !$age)
    jsonResponse(false, 'Patient ID, Full Name, Gender, and Age are required');

if (!in_array($gender, ['Male', 'Female', 'Other']))
    jsonResponse(false, 'Invalid gender value');

if(!is_numeric($age) || $age < 1 || $age > 120)
    jsonResponse(false, "Invalid age");

$db = getDB();

$stmt = $db->prepare('SELECT id FROM patients WHERE id = ?');
$stmt->execute([$patient_db_id]);
if (!$stmt->fetch()) jsonResponse(false, 'Patient Record not found!');

$stmt = $db->prepare('SELECT id FROM patients WHERE patient_id = ? AND id != ?');
$stmt->execute([$patient_id, $patient_db_id]);
if ($stmt->fetch()) jsonResponse(false, "Patient ID \"$patient_id\" is already used by another record");

$db->prepare(
    'UPDATE patients SET 
        patient_id      = ?,
        full_name       = ?,
        gender          = ?,
        age             = ?,
        height_cm       = ?,
        weight_kg       = ?,
        blood_pressure  = ?,
        blood_type      = ?,
        department      = ?,
        medical_history = ?
    WHERE id = ?'
)->execute([
    $patient_id,
    $full_name,
    $gender,
    (int)$age,
    $height !== '' ? (float)$height : null,
    $weight !== '' ? (float)$weight : null,
    $blood_pressure !== '' ? $blood_pressure : null,
    $blood_type,
    $department !== '' ? $department : null,
    $medical_history !== '' ? $medical_history : null,
    $patient_db_id,
]);

$delete_ids = json_decode($_POST['delete_file_ids'] ?? '[]', true);
if (!empty($delete_ids)) {
    $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
    $db->prepare("DELETE FROM medical_files WHERE id IN ($placeholders) AND patient_id = ?")
    ->execute([...$delete_ids, $patient_db_id]);
}

$uploaded = [];
$errors = [];

$allowed_signatures = [
    'pdf' => "\x25\x50\x44\x46",
    'jpg' => "\xFF\xD8\xFF",
    'png' => "\x89\x50\x4E\x47",
];

function getFileMagicTypeUpdate(string $path): ?string {
    global $allowed_signatures;
    $handle = fopen($path, 'rb');
    $bytes = fread($handle, 8);
    fclose($handle);
    foreach ($allowed_signatures as $type => $sig) {
        if (str_starts_with($bytes, $sig)) return $type;
    }

    return null;
}

if (!empty($_FILES['medical_files']['name'][0])) {
    $max_size = 10 * 1024 * 1024;
     $mime_map = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
     
    foreach ($_FILES['medical_files']['name'] as $i => $original_name) {
        if ($_FILES['medical_files']['error'] [$i] !== UPLOAD_ERR_OK) {
            $errors[] = "\"$original_name\" failed to upload"; continue;
        }

        $tmp = $_FILES['medical_files']['tmp_name'][$i];
        $size = $_FILES['medical_files']['size'][$i];

        if ($size > $max_size) {
            $errors[] = "\"$original_name\" exceeds the 10MB limit"; continue;
        }

        $detected = getFileMagicTypeUpdate($tmp);
        if ($detected === null) {
            $errors[] = "\"$original_name\" is not a valid file format"; continue;
        }

        $db->prepare(
            'INSERT INTO medical_files (patient_id, file_name, file_type, file_data, uploaded_by)
            VALUES (?, ?, ?, ?, ?)'
        ) ->execute([
            $patient_db_id, basename($original_name),
            $mime_map[$detected], file_get_contents($tmp), $_SESSION['user_id'],
        ]);

        $uploaded[] = basename($original_name);
    }
}

$msg = 'Patient record updated successfully.';
if ($uploaded) $msg .= '' .count($uploaded) . ' new file(s) uploaded';
if ($errors) $msg .= ' Warning: ' . implode('', $errors);

jsonResponse(true, $msg);