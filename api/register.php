<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/animals.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Nederīga metode', 405);
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

// Validation
if (!$email) {
    errorResponse('Nederīgs e-pasta formāts');
}

if (!isValidPassword($password)) {
    errorResponse('Parole jābūt vismaz 8 simboli gara');
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    errorResponse('Šis e-pasts jau ir reģistrēts');
}

// Generate random animal name
$animal_name = getRandomAnimalName();
$nickname = $animal_name;

// Hash password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert user
try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (email, password_hash, nickname, animal_name) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$email, $password_hash, $nickname, $animal_name]);
    
    $user_id = $pdo->lastInsertId();
    
    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['nickname'] = $nickname;
    
    successResponse([
        'nickname' => $nickname,
        'animal_name' => $animal_name
    ], 'Reģistrācija veiksmīga!');
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    errorResponse('Reģistrācijas kļūda. Mēģiniet vēlreiz.', 500);
}
