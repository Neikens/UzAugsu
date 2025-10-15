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

$count = (int)($_POST['count'] ?? 0);

// Validation
if ($count <= 0 || $count > 1000) {
    errorResponse('Nederīgs skaits (1-1000)');
}

try {
    // Insert pullup entry
    $stmt = $pdo->prepare(
        "INSERT INTO pullups (user_id, count, entry_date) 
         VALUES (?, ?, CURDATE())"
    );
    $stmt->execute([$_SESSION['user_id'], $count]);
    
    // Get today's total for this user
    $stmt = $pdo->prepare(
        "SELECT SUM(count) as total 
         FROM pullups 
         WHERE user_id = ? AND entry_date = CURDATE()"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    // Get user's all-time total
    $stmt = $pdo->prepare(
        "SELECT SUM(count) as all_time_total 
         FROM pullups 
         WHERE user_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $all_time = $stmt->fetch();
    
    successResponse([
        'added' => $count,
        'today_total' => (int)$result['total'],
        'all_time_total' => (int)$all_time['all_time_total']
    ], "Pievienotas {$count} pievilkšanās!");
    
} catch (PDOException $e) {
    error_log("Add pullups error: " . $e->getMessage());
    errorResponse('Kļūda pievienojot datus', 500);
}
