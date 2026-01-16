<?php
// LightBlog CMS Configuration
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'hoangmi5_lightblog');
define('DB_USER', 'hoangmi5_admin');
define('DB_PASS', 'h0angm1nh.');
define('SITE_URL', 'https://hoangminhmz.com/lite');
define('SITE_PATH', __DIR__);
define('CONTENT_PATH', __DIR__ . '/content');
define('BASE_PATH', '/lite/');
define('CURRENT_THEME', 'default');
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600);
define('SECURITY_KEY', '0743b48a0af8850b24c8e5ec38347f1ee6d7e1201499b033e0a1e9d80cd01e1a');
define('AUTOBLOG_ENABLED', true);
define('OPENAI_API_KEY', '');
define('CLAUDE_API_KEY', '');
define('GEMINI_API_KEY', 'AIzaSyDI3GL1bJQCrF4Vq13jeNaZq-CqEjXeys8');
define('UNSPLASH_API_KEY', '');
define('DEBUG_MODE', false);
define('SITE_DEBUG', false); // Added for AJAX error handling

error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('UTC');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Start session if not already started
if (!session_id()) {
    session_start();
}
