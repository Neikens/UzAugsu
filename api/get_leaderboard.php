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

try {
    // Pullups leaderboard (total km)
    $pullups_sql = "
        SELECT 
            u.nickname,
            u.id,
            SUM(p.count) as total_km,
            COUNT(DISTINCT p.entry_date) as active_days
        FROM users u
        JOIN pullups p ON u.id = p.user_id
        GROUP BY u.id
        ORDER BY total_km DESC
        LIMIT 50
    ";
    
    $pullups = $pdo->query($pullups_sql)->fetchAll();
    
    // Add rank
    foreach ($pullups as $index => &$row) {
        $row['rank'] = $index + 1;
        $row['is_current_user'] = ($row['id'] == $_SESSION['user_id']);
    }
    
    // Verse streak leaderboard (unique days with first verse)
    $verses_sql = "
        SELECT 
            u.nickname,
            u.id,
            COUNT(DISTINCT v.entry_date) as streak_days,
            MAX(v.entry_date) as last_entry
        FROM users u
        JOIN verses v ON u.id = v.user_id
        WHERE v.is_first_of_day = TRUE
        GROUP BY u.id
        ORDER BY streak_days DESC
        LIMIT 50
    ";
    
    $verses = $pdo->query($verses_sql)->fetchAll();
    
    // Add rank
    foreach ($verses as $index => &$row) {
        $row['rank'] = $index + 1;
        $row['is_current_user'] = ($row['id'] == $_SESSION['user_id']);
        $row['last_entry'] = formatLatvianDate($row['last_entry']);
    }
    
    // Get current user's position if not in top 50
    $current_user_pullups = null;
    $current_user_verses = null;
    
    if (!in_array($_SESSION['user_id'], array_column($pullups, 'id'))) {
        $stmt = $pdo->prepare("
            SELECT 
                u.nickname,
                SUM(p.count) as total_km,
                COUNT(DISTINCT p.entry_date) as active_days,
                (SELECT COUNT(*) + 1 FROM (
                    SELECT user_id, SUM(count) as total
                    FROM pullups
                    GROUP BY user_id
                    HAVING total > SUM(p.count)
                ) sub) as rank
            FROM users u
            JOIN pullups p ON u.id = p.user_id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user_pullups = $stmt->fetch();
        if ($current_user_pullups) {
            $current_user_pullups['is_current_user'] = true;
        }
    }
    
    if (!in_array($_SESSION['user_id'], array_column($verses, 'id'))) {
        $stmt = $pdo->prepare("
            SELECT 
                u.nickname,
                COUNT(DISTINCT v.entry_date) as streak_days,
                MAX(v.entry_date) as last_entry,
                (SELECT COUNT(*) + 1 FROM (
                    SELECT user_id, COUNT(DISTINCT entry_date) as days
                    FROM verses
                    WHERE is_first_of_day = TRUE
                    GROUP BY user_id
                    HAVING days > COUNT(DISTINCT v.entry_date)
                ) sub) as rank
            FROM users u
            JOIN verses v ON u.id = v.user_id
            WHERE u.id = ? AND v.is_first_of_day = TRUE
            GROUP BY u.id
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user_verses = $stmt->fetch();
        if ($current_user_verses) {
            $current_user_verses['is_current_user'] = true;
            $current_user_verses['last_entry'] = formatLatvianDate($current_user_verses['last_entry']);
        }
    }
    
    jsonResponse([
        'pullups' => $pullups,
        'verses' => $verses,
        'current_user_pullups' => $current_user_pullups,
        'current_user_verses' => $current_user_verses
    ]);
    
} catch (PDOException $e) {
    error_log("Leaderboard error: " . $e->getMessage());
    errorResponse('Kļūda iegūstot rezultātus', 500);
}
