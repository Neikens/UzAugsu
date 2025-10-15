<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Simple router
$route = $_GET['route'] ?? 'dashboard';

// Public routes (no login required)
$publicRoutes = ['login', 'register'];

// Check if route requires authentication
if (!in_array($route, $publicRoutes)) {
    requireLogin();
}

// Route to appropriate page
switch ($route) {
    case 'login':
        require __DIR__ . '/auth/login.html';
        break;
    
    case 'register':
        require __DIR__ . '/auth/register.html';
        break;
    
    case 'dashboard':
        require __DIR__ . '/pages/dashboard.php';
        break;
    
    case 'leaderboard':
        require __DIR__ . '/pages/leaderboard.php';
        break;
    
    case 'treasury':
        require __DIR__ . '/pages/treasury.php';
        break;
    
    case 'profile':
        require __DIR__ . '/pages/profile.php';
        break;
    
    default:
        // If logged in, show dashboard; otherwise login
        if (isLoggedIn()) {
            require __DIR__ . '/pages/dashboard.php';
        } else {
            require __DIR__ . '/auth/login.html';
        }
        break;
}
?>