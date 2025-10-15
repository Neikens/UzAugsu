<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode([]));
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    die(json_encode([]));
}

try {
    // Search in bible_references table
    $stmt = $pdo->prepare(
        "SELECT DISTINCT full_reference 
         FROM bible_references 
         WHERE full_reference LIKE ? 
         OR book_lv LIKE ?
         ORDER BY full_reference
         LIMIT 20"
    );
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // If no results from bible_references, try popular_verses
    if (empty($results)) {
        $stmt = $pdo->prepare(
            "SELECT DISTINCT reference 
             FROM popular_verses 
             WHERE reference LIKE ? 
             ORDER BY reference
             LIMIT 20"
        );
        $stmt->execute([$searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo json_encode(array_values($results));
    
} catch (PDOException $e) {
    error_log("Search verses error: " . $e->getMessage());
    die(json_encode([]));
}