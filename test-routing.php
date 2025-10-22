<?php
/**
 * Test routing and BASE_PATH handling
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Routing Test</title></head><body>";
echo "<h1>Routing Debug Test</h1>";

// Load config
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    echo "<p>✓ Config loaded</p>";
} else {
    die("<p style='color:red'>✗ Config not found</p></body></html>");
}

echo "<h2>Configuration Values:</h2>";
echo "<pre>";
echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NOT DEFINED') . "\n";
echo "SITE_PATH: " . (defined('SITE_PATH') ? SITE_PATH : 'NOT DEFINED') . "\n";
echo "BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "\n";
echo "ACTIVE_THEME: " . (defined('ACTIVE_THEME') ? ACTIVE_THEME : 'NOT DEFINED') . "\n";
echo "CURRENT_THEME: " . (defined('CURRENT_THEME') ? CURRENT_THEME : 'NOT DEFINED') . "\n";
echo "</pre>";

echo "<h2>Server Variables:</h2>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "</pre>";

// Load Router
require_once SITE_PATH . '/core/Router.php';

echo "<h2>Router Test:</h2>";
echo "<pre>";

$testUris = [
    '/lite/',
    '/lite',
    '/lite/post/test-1',
    '/lite/category/tech',
    '/lite/tag/news',
];

foreach ($testUris as $testUri) {
    echo "\nTest URI: $testUri\n";
    $_SERVER['REQUEST_URI'] = $testUri;
    $processed = Router::getCurrentUri();
    echo "  → Processed to: $processed\n";

    // Test if it matches homepage pattern
    if (preg_match('#^/$#', $processed)) {
        echo "  → MATCHES homepage pattern #^/$#\n";
    } else {
        echo "  → Does NOT match homepage pattern\n";
    }

    // Test if it matches post pattern
    if (preg_match('#^/post/([a-z0-9-]+)$#', $processed, $matches)) {
        echo "  → MATCHES post pattern, slug: " . $matches[1] . "\n";
    }
}

echo "</pre>";

echo "<h2>Theme File Check:</h2>";
echo "<pre>";

if (defined('ACTIVE_THEME')) {
    $theme = ACTIVE_THEME;
    echo "Using ACTIVE_THEME: $theme\n";
} elseif (defined('CURRENT_THEME')) {
    $theme = CURRENT_THEME;
    echo "Using CURRENT_THEME: $theme\n";
} else {
    $theme = 'default';
    echo "No theme constant defined, using: $theme\n";
}

$theme_path = SITE_PATH . '/themes/' . $theme;
echo "\nTheme path: $theme_path\n";
echo "Theme exists: " . (is_dir($theme_path) ? 'YES' : 'NO') . "\n";

$files = ['index.php', 'header.php', 'footer.php', 'single.php', '404.php'];
foreach ($files as $file) {
    $full_path = $theme_path . '/' . $file;
    echo "  $file: " . (file_exists($full_path) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "</pre>";

echo "</body></html>";
