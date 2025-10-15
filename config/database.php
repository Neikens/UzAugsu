<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'uzaugsu_lv');
define('DB_USER', 'uzaugsu_user');
define('DB_PASS', 'parole123');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Datubāzes savienojuma kļūda. Lūdzu mēģiniet vēlreiz.'
    ]));
}

date_default_timezone_set('Europe/Riga');
