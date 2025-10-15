<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    errorResponse('Nav autorizēts', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Nederīga metode', 405);
}

$nickname = trim($_POST['nickname'] ?? '');

// Validation
if (empty($nickname)) {
    errorResponse('Segvārds nevar būt tukšs');
}

if (strlen($nickname) < 2 || strlen($nickname) > 100) {
    errorResponse('Segvārdam jābūt 2-100 simboliem');
}

// Check if nickname is already taken
$stmt = $pdo->prepare("SELECT id FROM users WHERE nickname = ? AND id != ?");
$stmt->execute([$nickname, $_SESSION['user_id']]);

if ($stmt->fetch()) {
    errorResponse('Šis segvārds jau ir aizņemts');
}

try {
    $stmt = $pdo->prepare("UPDATE users SET nickname = ? WHERE id = ?");
    $stmt->execute([$nickname, $_SESSION['user_id']]);
    
    // Update session
    $_SESSION['nickname'] = $nickname;
    
    successResponse([
        'nickname' => $nickname
    ], 'Segvārds atjaunots!');
    
} catch (PDOException $e) {
    error_log("Update profile error: " . $e->getMessage());
    errorResponse('Kļūda atjaunojot profilu', 500);
}
