<?php
// logout.php
session_start();
require 'db.php';
require 'auth_helpers.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// destroy session
$_SESSION = [];
session_destroy();

// clear remember token in DB + cookie
if ($userId) {
    clear_remember_cookie($conn, $userId);
} else {
    // even without session, still clear cookie if present
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

// redirect to homepage
header("Location: index.html");
exit;
