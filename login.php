<?php
// login.php
session_start();
require 'db.php';
require 'auth_helpers.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) json_response(['status' => 'error', 'message' => 'Invalid request body'], 400);

$email    = strtolower(trim($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$remember = !empty($data['remember']);

if ($email === '' || $password === '') {
    json_response(['status' => 'error', 'message' => 'Email and password are required.'], 422);
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['status' => 'error', 'message' => 'Invalid email or password.'], 401);
}

// Set session
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['first_name']   = $user['first_name'];
$_SESSION['email']   = $user['email'];

// Update last_login
$stmt = $conn->prepare("UPDATE users SET last_login=NOW() WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

// Remember me
if ($remember) {
    make_remember_cookie($conn, $_SESSION['user_id']);
}

json_response(['status' => 'success', 'message' => 'Login successful!']);
