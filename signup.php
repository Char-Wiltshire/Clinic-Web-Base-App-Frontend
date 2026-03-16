<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

verifyCsrfToken();

$full_name = trim($_POST['full_name'] ?? '');
$department = trim($_POST['department']       ?? '');
$email      = trim($_POST['email']            ?? '');
$password   = $_POST['password']              ?? '';
$confirm_pw = $_POST['confirm_password']      ?? '';

if (!$full_name || !$department || !$email || !$password || !$confirm_pw) {
    jsonResponse(false, "All fields are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, "Invalid Email Address.");
}

if (strlen($password) < 8) {
    jsonResponse(false, 'Password must be at least 8 characters long.');
}

if ($password !== $confirm_pw) {
    jsonResponse(false, "Password does not match.");
}

$db = getDB();
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(false, 'An account with this email already exists.');
}

$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$db->prepare(
    'INSERT INTO users (full_name, department, email, password, is_approved) VALUES (?, ?, ?, ?, 0)'
)->execute([$full_name, $department, $email, $hashed]);

jsonResponse(true, 'Account created. Please wait for admin approval before logging in.');