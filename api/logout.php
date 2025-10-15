<?php
session_start();

// Destroy session
$_SESSION = [];

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

session_destroy();

// Redirect to login
header('Location: /auth/login.html');
exit;
