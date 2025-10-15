<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    errorResponse('Nav autorizēts', 401);
}

$date = $_GET['date'] ?? 'today';

try {
    // Determine date condition
    if ($date === 'today') {
        $date_condition = "v.entry_date = CURDATE()";
    } elseif ($date === 'yesterday') {
        $date_condition = "v.entry_date = CURDATE() - INTERVAL 1 DAY";
    } else {
        // Specific date (format: YYYY-MM-DD)
        $date_condition = "v.entry_date = :date";
    }
    
    $sql = "
        SELECT 
            v.id,
            v.verse_reference,
            v.verse_text,
            v.entry_time,
            v.is_first_of_day,
            u.nickname,
            u.id as user_id
        FROM verses v
        JOIN users u ON v.user_id = u.id
        WHERE {$date_condition}
        ORDER BY v.entry_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    
    if ($date !== 'today' && $date !== 'yesterday') {
        $stmt->bindValue(':date', $date);
    }
    
    $stmt->execute();
    $verses = $stmt->fetchAll();
    
    // Format data
    foreach ($verses as &$verse) {
        $verse['time'] = formatLatvianTime($verse['entry_time']);
        $verse['relative_time'] = getRelativeTime($verse['entry_time']);
        $verse['is_current_user'] = ($verse['user_id'] == $_SESSION['user_id']);
    }
    
    jsonResponse([
        'date' => $date,
        'verses' => $verses,
        'count' => count($verses)
    ]);
    
} catch (PDOException $e) {
    error_log("Get verses error: " . $e->getMessage());
    errorResponse('Kļūda iegūstot pantus', 500);
}
