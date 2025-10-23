<?php
/**
 * LightBlog CMS Configuration
 */

// Database Configuration
define('DB_TYPE', 'mysql'); // Using MySQL based on previous errors

// MySQL Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'hoangmi5_lightblog'); // Based on previous error messages
define('DB_USER', 'hoangmi5_lightblog');
define('DB_PASS', getenv('DB_PASSWORD') ?: ''); // Use environment variable or empty

// Site Configuration
define('SITE_URL', 'https://hoangminhmz.com');
define('SITE_PATH', __DIR__);
define('CONTENT_PATH', __DIR__ . '/content');
define('BASE_PATH', '/lite/'); // Subdirectory path

// Theme
define('CURRENT_THEME', 'default');

// Cache
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // Default cache time in seconds

// Security
define('SECURITY_KEY', 'lightblog-cms-secure-key-' . md5(__DIR__));

// Debug Mode
define('SITE_DEBUG', false); // Set to true for debugging
define('DEBUG_MODE', false);

if (DEBUG_MODE || SITE_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Session Configuration
if (!session_id()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}
