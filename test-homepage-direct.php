<?php
/**
 * Direct test simulating homepage rendering
 * This mimics exactly what index.php does for homepage
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== STARTING HOMEPAGE SIMULATION ===<br><br>";

try {
    echo "1. Loading config...<br>";
    require_once __DIR__ . '/config.php';
    echo "✓ Config loaded<br><br>";

    echo "2. Loading core classes...<br>";
    require_once SITE_PATH . '/core/Database.php';
    require_once SITE_PATH . '/core/Cache.php';
    require_once SITE_PATH . '/core/Router.php';
    require_once SITE_PATH . '/core/Template.php';
    echo "✓ All core classes loaded<br><br>";

    echo "3. Initializing...<br>";
    $db = Database::getInstance();
    echo "✓ Database instance created<br>";

    $cache = new Cache();
    echo "✓ Cache created<br>";

    $router = new Router();
    echo "✓ Router created<br>";

    Template::init();
    echo "✓ Template initialized<br><br>";

    echo "4. Testing Router::getCurrentUri()...<br>";
    $uri = Router::getCurrentUri();
    echo "✓ Current URI: " . htmlspecialchars($uri) . "<br><br>";

    echo "5. Fetching posts (simulating homepage route)...<br>";
    $posts = $cache->remember('homepage_posts', function() use ($db) {
        return $db->query("
            SELECT * FROM posts
            WHERE status = 'published'
            ORDER BY published_at DESC
            LIMIT 10
        ");
    }, 600);
    echo "✓ Found " . count($posts) . " posts<br><br>";

    echo "6. About to include theme index.php...<br>";
    $theme_file = SITE_PATH . '/themes/' . (defined('CURRENT_THEME') ? CURRENT_THEME : 'default') . '/index.php';
    echo "Theme file: " . htmlspecialchars($theme_file) . "<br>";
    echo "File exists: " . (file_exists($theme_file) ? 'YES' : 'NO') . "<br><br>";

    echo "=== NOW RENDERING THEME ===<br>";
    echo "======================================<br><br>";

    // Include the theme - any errors will show up here
    ob_start();
    include $theme_file;
    $output = ob_get_clean();

    echo "<br><br>======================================<br>";
    echo "=== THEME RENDERING COMPLETE ===<br><br>";

    echo "Output length: " . strlen($output) . " bytes<br>";

    if (strlen($output) > 0) {
        echo "✓ Theme produced output<br><br>";
        echo "First 200 characters of output:<br>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 200)) . "</pre><br>";

        echo "<hr><h2>ACTUAL RENDERED OUTPUT:</h2>";
        echo $output;
    } else {
        echo "<span style='color:red'>✗ Theme produced NO OUTPUT - there is an error!</span><br>";
    }

} catch (Exception $e) {
    echo "<br><br><div style='color:red;border:2px solid red;padding:10px;'>";
    echo "<h2>FATAL ERROR CAUGHT</h2>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
} catch (Error $e) {
    echo "<br><br><div style='color:red;border:2px solid red;padding:10px;'>";
    echo "<h2>FATAL ERROR CAUGHT</h2>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<br><br>=== TEST COMPLETE ===";
