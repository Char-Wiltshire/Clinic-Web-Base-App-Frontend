<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method. Please use POST.');    
}

verifyCsrfToken();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password ) {
    jsonResponse(false, 'Email and password are required.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format.');
}

$db = getDB();
$stmt = $db->prepare(
    'SELECT id, full_name, department, password, is_approved,
        login_attempts, locked_until
    FROM users WHERE email = ?'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if(!$user) {
    jsonResponse(false, 'Invalid Email Address or Password');
}

if($user['locked_until']&& new DateTime() < new DateTime($user['locked_until'])) {
    $remaining = (new DateTime($user['locked_until']))->diff(new DateTime());
    $mins = $remaining->i + 1;
    jsonResponse(false, "Too many failed attempts. Account locked. Try again in {$mins} minute(s).");
}

if (!password_verify($password, $user['password'])) {
    $attempts = (int)$user['login_attempts'] + 1;

    if($attempts >= 5) {
        $lockUntil = (new DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');
        $db->prepare('UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?')
            ->execute([$attempts,$lockUntil, $user['id']]);
        jsonResponse(false, 'Too many failed attempts. Your account has been locked for 15 minutes');
    }

    $db->prepare('UPDATE users SET login_attempts = ? WHERE id = ?')
        ->execute([$attempts, $user['id']]);

    $left = 5 - $attempts;
    jsonResponse(false, "Invalid Email or Password. {$left} attempt(s) remaining before lockout.");
}

if (!$user['is_approved']) {
    jsonResponse(false, "Your account is pending for approval. Please check back later.");
}

$db->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?')
    ->execute([$user['id']]);

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['department'] = $user['department'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

jsonResponse(true, 'Login Successful.', ['redirect' => 'dashboard.html']);