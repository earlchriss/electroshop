<?php
// Always return JSON
header('Content-Type: application/json');

// Hide warnings/notices from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

function send_json($arr)
{
    echo json_encode($arr);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// register.php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// read JSON body
$data = json_decode(file_get_contents("php://input"), true);

$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName'] ?? '');
$email     = trim($data['email'] ?? '');
$phone     = trim($data['phone'] ?? '');
$password  = $data['password'] ?? '';

// validate empty fields
if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// check if email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    send_json(['status' => 'error', 'message' => 'Email already registered.']);

    $check->close();
    exit;
}
$check->close();

// hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// insert user with is_verified = 0
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
$stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashed);

if (!$stmt->execute()) {
    send_json(['status' => 'error', 'message' => 'Email already registered.']);

    exit;
}
$userId = $stmt->insert_id;
$stmt->close();

// generate OTP
$otp = rand(100000, 999999);
$otpExpires = date("Y-m-d H:i:s", strtotime("+10 minutes"));
$update = $conn->prepare("UPDATE users SET otp = ?, otp_expires = ? WHERE id = ?");
$update->bind_param("ssi", $otp, $otpExpires, $userId);
$update->execute();
$update->close();

// send OTP email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'electroshopph.org@gmail.com'; // your Gmail
    $mail->Password   = 'phmj agse engs mvuf'; // app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('electroshopph.org@gmail.com', 'Electroshop');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'ElectroHub Email Verification';

    $mail->Body = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f6fa; font-family:Arial, sans-serif;">
  <table align="center" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; margin:auto; background-color:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    <tr>
      <td style="background:#0d6efd; padding:20px; text-align:center; border-top-left-radius:8px; border-top-right-radius:8px;">
        <h2 style="color:#ffffff; margin:0; font-size:22px;">Electroshop</h2>
      </td>
    </tr>
    <tr>
      <td style="padding:30px; color:#333;">
        <h3 style="margin:0 0 15px;">Hello ' . htmlspecialchars($firstName) . ',</h3>
        <p style="margin:0 0 20px; font-size:15px; line-height:1.6;">
          Thank you for registering with <strong>Electroshop</strong>! To complete your registration, please use the following One-Time Password (OTP) to verify your email address:
        </p>
        <div style="text-align:center; margin:25px 0;">
          <span style="display:inline-block; background:#f0f4ff; color:#0d6efd; font-size:24px; letter-spacing:5px; padding:15px 30px; border-radius:6px; font-weight:bold; border:1px solid #dce3f7;">
            ' . htmlspecialchars($otp) . '
          </span>
        </div>
        <p style="margin:0 0 10px; font-size:15px; line-height:1.6; text-align:center;">
          This code will expire in <strong>10 minutes</strong>.
        </p>
        <p style="margin:25px 0 0; font-size:14px; color:#666; text-align:center;">
          If you did not request this, please ignore this email or contact our support team.
        </p>
      </td>
    </tr>
    <tr>
      <td style="background:#f9f9f9; text-align:center; padding:15px; border-bottom-left-radius:8px; border-bottom-right-radius:8px; font-size:12px; color:#999;">
        © ' . date("Y") . ' Electroshop. All rights reserved.
      </td>
    </tr>
  </table>
</body>
</html>
';


    if (!$mail->send()) {
        send_json(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    } else {
        send_json(['status' => 'success', 'message' => 'OTP sent to your email.']);
    }
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => 'Mailer Exception: ' . $e->getMessage()]);
}

// return success with userId
echo json_encode([
    'status' => 'success',
    'message' => 'Registered successfully. Please verify OTP.',
    'userId' => $userId
]);
