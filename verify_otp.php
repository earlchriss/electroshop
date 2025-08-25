<?php
session_start();
require 'db.php';
require 'auth_helpers.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$otp   = trim($data['otp'] ?? '');

if (!$email || !$otp) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// Find user by email
$stmt = $conn->prepare("SELECT id, otp, otp_expires FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

$user = $result->fetch_assoc();

// Check OTP validity
if ($user['otp'] !== $otp) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP.']);
    exit;
}
if (strtotime($user['otp_expires']) < time()) {
    echo json_encode(['status' => 'error', 'message' => 'OTP expired.']);
    exit;
}

// Mark verified and clear OTP
$update = $conn->prepare("UPDATE users SET is_verified = 1, otp = NULL, otp_expires = NULL WHERE id = ?");
$update->bind_param("i", $user['id']);
$update->execute();

// Log user in
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $email;

echo json_encode(['status' => 'success', 'message' => 'Email verified successfully!']);
