<?php
define('SITE_NAME', 'Garīgā Uzlāde');
define('SITE_URL', 'https://www.uzaugsu.lv');
define('SITE_EMAIL', 'info@uzaugsu.lv');

define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24 * 7);
define('ITEMS_PER_PAGE', 50);
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/uzaugsu/php_errors.log');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');