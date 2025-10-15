<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Nav autorizēts']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Nederīga metode']));
}

$reference = trim($_POST['reference'] ?? '');
$text = trim($_POST['text'] ?? '');

// Validation
if (empty($reference)) {
    die(json_encode(['success' => false, 'message' => 'Ievadi panta atsauci']));
}

if (strlen($reference) > 150) {
    die(json_encode(['success' => false, 'message' => 'Atsauce pārāk gara']));
}

try {
    // Check for duplicate verse today
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count 
         FROM verses 
         WHERE user_id = ? 
         AND verse_reference = ? 
         AND entry_date = CURDATE()"
    );
    $stmt->execute([$_SESSION['user_id'], $reference]);
    $duplicate = $stmt->fetch();
    
    if ($duplicate['count'] > 0) {
        die(json_encode([
            'success' => false, 
            'message' => 'Šo pantu šodien jau esi pievienojis!'
        ]));
    }
    
    // Check if this is the first verse today (FIXED: explicit integer)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as count 
         FROM verses 
         WHERE user_id = ? AND entry_date = CURDATE()"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    // Convert to explicit integer (0 or 1)
    $is_first = ($result['count'] == 0) ? 1 : 0;
    
    // Insert verse
    $stmt = $pdo->prepare(
        "INSERT INTO verses (user_id, verse_reference, verse_text, entry_date, is_first_of_day) 
         VALUES (?, ?, ?, CURDATE(), ?)"
    );
    $stmt->execute([$_SESSION['user_id'], $reference, $text, $is_first]);
    
    // Get total unique days with first verses
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT entry_date) as total_days
         FROM verses 
         WHERE user_id = ? AND is_first_of_day = 1"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
    
    // Get TODAY's total verse count
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as today_count
         FROM verses 
         WHERE user_id = ? AND entry_date = CURDATE()"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $today_stats = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'is_first' => ($is_first === 1),
        'total_days' => (int)($stats['total_days'] ?? 0),
        'today_count' => (int)($today_stats['today_count'] ?? 0),
        'reference' => $reference,
        'message' => ($is_first === 1) ? '🎉 Pirmais pants šodien!' : '✅ Pants pievienots!'
    ]);
    
} catch (PDOException $e) {
    error_log("Add verse error for user {$_SESSION['user_id']}: " . $e->getMessage());
    die(json_encode([
        'success' => false, 
        'message' => 'Datubāzes kļūda'
    ]));
}