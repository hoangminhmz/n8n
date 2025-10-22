<?php
/**
 * LightBlog CMS Configuration
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_TYPE', 'sqlite'); // 'sqlite' or 'mysql'

// MySQL Settings (only needed if DB_TYPE is 'mysql')
define('DB_HOST', 'localhost');
define('DB_NAME', 'lightblog');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_URL', 'http://localhost');
define('SITE_PATH', __DIR__);
define('CONTENT_PATH', __DIR__ . '/content');
define('BASE_PATH', '/'); // Set to subdirectory path if not in root

// Theme
define('CURRENT_THEME', 'default');

// Cache
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // Default cache time in seconds

// Security
define('SECURITY_KEY', 'CHANGE-THIS-TO-A-RANDOM-STRING');

// AI Configuration (Optional - for auto-blogging feature)
define('AUTOBLOG_ENABLED', false);

// OpenAI
define('OPENAI_API_KEY', '');

// Anthropic Claude
define('CLAUDE_API_KEY', '');

// Google Gemini
define('GEMINI_API_KEY', '');

// Affiliate Networks (Optional)
define('AMAZON_ASSOCIATE_ID', '');
define('AMAZON_ACCESS_KEY', '');
define('AMAZON_SECRET_KEY', '');

// Email Settings (Optional)
define('ADMIN_EMAIL', 'admin@yourdomain.com');
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// CDN (Optional)
// define('CDN_URL', 'https://cdn.yourdomain.com');

// Debug Mode (set to false in production)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
