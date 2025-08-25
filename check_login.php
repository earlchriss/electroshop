<?php
// check_login.php
session_start();
require 'db.php';
require 'auth_helpers.php';

if (isset($_SESSION['user_id'])) {
    json_response(['loggedIn' => true, 'email' => $_SESSION['email'] ?? '']);
}

// try cookie auto-login
if (try_auto_login($conn)) {
    json_response(['loggedIn' => true, 'email' => $_SESSION['email'] ?? '']);
}

json_response(['loggedIn' => false]);
