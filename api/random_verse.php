<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query(
        "SELECT reference, text_lv 
         FROM popular_verses 
         ORDER BY RAND() 
         LIMIT 1"
    );
    
    $verse = $stmt->fetch();
    
    if ($verse) {
        jsonResponse([
            'reference' => $verse['reference'],
            'text' => $verse['text_lv']
        ]);
    } else {
        errorResponse('Nav atrasts neviens pants');
    }
    
} catch (PDOException $e) {
    error_log("Random verse error: " . $e->getMessage());
    errorResponse('Kļūda iegūstot pantu', 500);
}
