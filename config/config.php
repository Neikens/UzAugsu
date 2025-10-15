<?php
// Site configuration
define('SITE_NAME', 'Garīgā Uzlāde');
define('SITE_URL', 'http://localhost'); // Change for production
define('SITE_EMAIL', 'info@uzaugsu.lv');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days

// Pagination
define('ITEMS_PER_PAGE', 50);

// File upload (if needed later)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Session configuration
//ini_set('session.cookie_httponly', 1);
//ini_set('session.use_only_cookies', 1);
//ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
//ini_set('session.cookie_samesite', 'Lax');

