<?php
// db.php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'electrohub';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die('DB connection failed');
}
mysqli_set_charset($conn, 'utf8mb4');
