<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(array('success' => false, 'message' => 'Nav autorizēts')));
}

try {
    // ✅ FIXED: Added all columns to GROUP BY for MySQL 5.5
    $pullups_sql = "
        SELECT 
            u.nickname,
            u.id,
            SUM(p.count) as total_km,
            COUNT(DISTINCT p.entry_date) as active_days
        FROM users u
        JOIN pullups p ON u.id = p.user_id
        GROUP BY u.id, u.nickname
        ORDER BY total_km DESC
        LIMIT 50
    ";
    
    $pullups = $pdo->query($pullups_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Add rank
    foreach ($pullups as $index => &$row) {
        $row['rank'] = $index + 1;
        $row['is_current_user'] = ($row['id'] == $_SESSION['user_id']);
    }
    unset($row);
    
    // ✅ FIXED: Changed TRUE to 1 for MySQL 5.5 compatibility
    $verses_sql = "
        SELECT 
            u.nickname,
            u.id,
            COUNT(DISTINCT v.entry_date) as streak_days,
            MAX(v.entry_date) as last_entry
        FROM users u
        JOIN verses v ON u.id = v.user_id
        WHERE v.is_first_of_day = 1
        GROUP BY u.id, u.nickname
        ORDER BY streak_days DESC
        LIMIT 50
    ";
    
    $verses = $pdo->query($verses_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Add rank
    foreach ($verses as $index => &$row) {
        $row['rank'] = $index + 1;
        $row['is_current_user'] = ($row['id'] == $_SESSION['user_id']);
        $row['last_entry'] = formatLatvianDate($row['last_entry']);
    }
    unset($row);
    
    // Get current user's position if not in top 50
    $current_user_pullups = null;
    $current_user_verses = null;
    
    // Check if user is in pullups leaderboard
    $userInPullups = false;
    foreach ($pullups as $p) {
        if ($p['id'] == $_SESSION['user_id']) {
            $userInPullups = true;
            break;
        }
    }
    
    if (!$userInPullups) {
        $stmt = $pdo->prepare("
            SELECT 
                u.nickname,
                SUM(p.count) as total_km,
                COUNT(DISTINCT p.entry_date) as active_days
            FROM users u
            JOIN pullups p ON u.id = p.user_id
            WHERE u.id = ?
            GROUP BY u.id, u.nickname
        ");
        $stmt->execute(array($_SESSION['user_id']));
        $current_user_pullups = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_user_pullups) {
            // Calculate rank
            $rankStmt = $pdo->prepare("
                SELECT COUNT(*) + 1 as rank
                FROM (
                    SELECT user_id, SUM(count) as total
                    FROM pullups
                    GROUP BY user_id
                    HAVING total > ?
                ) sub
            ");
            $rankStmt->execute(array($current_user_pullups['total_km']));
            $rankResult = $rankStmt->fetch(PDO::FETCH_ASSOC);
            $current_user_pullups['rank'] = $rankResult['rank'];
            $current_user_pullups['is_current_user'] = true;
        }
    }
    
    // Check if user is in verses leaderboard
    $userInVerses = false;
    foreach ($verses as $v) {
        if ($v['id'] == $_SESSION['user_id']) {
            $userInVerses = true;
            break;
        }
    }
    
    if (!$userInVerses) {
        $stmt = $pdo->prepare("
            SELECT 
                u.nickname,
                COUNT(DISTINCT v.entry_date) as streak_days,
                MAX(v.entry_date) as last_entry
            FROM users u
            JOIN verses v ON u.id = v.user_id
            WHERE u.id = ? AND v.is_first_of_day = 1
            GROUP BY u.id, u.nickname
        ");
        $stmt->execute(array($_SESSION['user_id']));
        $current_user_verses = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_user_verses) {
            // Calculate rank
            $rankStmt = $pdo->prepare("
                SELECT COUNT(*) + 1 as rank
                FROM (
                    SELECT user_id, COUNT(DISTINCT entry_date) as days
                    FROM verses
                    WHERE is_first_of_day = 1
                    GROUP BY user_id
                    HAVING days > ?
                ) sub
            ");
            $rankStmt->execute(array($current_user_verses['streak_days']));
            $rankResult = $rankStmt->fetch(PDO::FETCH_ASSOC);
            $current_user_verses['rank'] = $rankResult['rank'];
            $current_user_verses['is_current_user'] = true;
            $current_user_verses['last_entry'] = formatLatvianDate(
                $current_user_verses['last_entry']
            );
        }
    }
    
    echo json_encode(array(
        'success' => true,
        'pullups' => $pullups,
        'verses' => $verses,
        'current_user_pullups' => $current_user_pullups,
        'current_user_verses' => $current_user_verses
    ));
    
} catch (PDOException $e) {
    error_log("Leaderboard error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Kļūda iegūstot rezultātus'
    ));
}