<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
requireLogin();
header('Content-Type: application/json');

$db = getDB();
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $like ='%' . $search . '%';
    $stmt = $db->prepare(
        'SELECT p.id, p.patient_id, p.full_name, p.gender, p.age,
                p.department, p.blood_type, p.updated_at,
                COUNT(f.id) AS file_count
        FROM    patients p
        LEFT JOIN medical_files f ON f.patient_id = p.id
        WHERE p.patient_id LIKE ? OR p.full_name LIKE ? OR p.department LIKE ?
        GROUP BY p.id ORDER BY p.updated_at DESC'
    );
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->prepare(
        'SELECT p.id, p.patient_id, p.full_name, p.gender, p.age,
                p.department, p.blood_type, p.updated_at,
                COUNT(f.id) AS file_count
        FROM patients p
        LEFT JOIN medical_files f ON f.patient_id = p.id
        GROUP BY p.id ORDER BY p.updated_at DESC'
    );
    $stmt->execute();
}

$patients = $stmt->fetchAll();
foreach($patients as &$p) {
    $p['updated_at'] = date('Y-m-d', strtotime($p['updated_at']));
}

$total_patients = (int)$db->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$total_files = (int)$db->query('SELECT COUNT(*) FROM medical_files')->fetchColumn();

jsonResponse(true, 'OK', [
    'patients' => $patients,
    'total_patients' => $total_patients,
    'total_files' => $total_files,
]);