<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Nederīga metode', 405);
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    errorResponse('Aizpildiet visus laukus');
}

// Get user
$stmt = $pdo->prepare("SELECT id, password_hash, nickname FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    errorResponse('Nepareizs e-pasts vai parole');
}

// Update last login
$stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$stmt->execute([$user['id']]);

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['nickname'] = $user['nickname'];

successResponse([
    'nickname' => $user['nickname']
], 'Pieslēgšanās veiksmīga!');
