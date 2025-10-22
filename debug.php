<?php
/**
 * LightBlog CMS - Debug Script
 * Shows PHP errors to help diagnose issues
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo '<h1>LightBlog Debug Information</h1>';
echo '<style>body { font-family: monospace; padding: 20px; } h2 { color: #667eea; margin-top: 20px; } .success { color: green; } .error { color: red; }</style>';

echo '<h2>1. Checking config.php...</h2>';
if (file_exists(__DIR__ . '/config.php')) {
    echo '<div class="success">✓ config.php exists</div>';
    try {
        require_once __DIR__ . '/config.php';
        echo '<div class="success">✓ config.php loaded successfully</div>';

        echo '<h3>Constants defined:</h3>';
        $constants = ['DB_TYPE', 'SITE_URL', 'SITE_PATH', 'BASE_PATH', 'CONTENT_PATH'];
        foreach ($constants as $const) {
            if (defined($const)) {
                echo '<div class="success">✓ ' . $const . ' = ' . htmlspecialchars(constant($const)) . '</div>';
            } else {
                echo '<div class="error">✗ ' . $const . ' NOT DEFINED</div>';
            }
        }
    } catch (Exception $e) {
        echo '<div class="error">✗ Error loading config.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="error">✗ config.php NOT FOUND</div>';
    die('Cannot continue without config.php');
}

echo '<h2>2. Checking core files...</h2>';
$coreFiles = [
    'core/Database.php',
    'core/Auth.php',
    'core/Cache.php',
    'core/Router.php',
    'core/Template.php'
];

foreach ($coreFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo '<div class="success">✓ ' . $file . ' exists</div>';
    } else {
        echo '<div class="error">✗ ' . $file . ' NOT FOUND</div>';
    }
}

echo '<h2>3. Testing Database connection...</h2>';
try {
    require_once SITE_PATH . '/core/Database.php';
    echo '<div class="success">✓ Database.php loaded</div>';

    $db = Database::getInstance();
    echo '<div class="success">✓ Database connection successful</div>';

    // Test query
    $result = $db->queryOne("SELECT COUNT(*) as count FROM users");
    echo '<div class="success">✓ Database query successful - ' . $result->count . ' users found</div>';

} catch (Exception $e) {
    echo '<div class="error">✗ Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '<h2>4. Testing Auth class...</h2>';
try {
    require_once SITE_PATH . '/core/Auth.php';
    echo '<div class="success">✓ Auth.php loaded</div>';

    $auth = new Auth();
    echo '<div class="success">✓ Auth class instantiated</div>';

} catch (Exception $e) {
    echo '<div class="error">✗ Auth error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '<h2>5. Testing admin/login.php...</h2>';
try {
    ob_start();
    include __DIR__ . '/admin/login.php';
    $output = ob_get_clean();
    echo '<div class="success">✓ admin/login.php loaded without errors</div>';
    echo '<p>If you see this, the login page should work. Try accessing it directly.</p>';
} catch (Exception $e) {
    echo '<div class="error">✗ Error in admin/login.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '<h2>6. PHP Information</h2>';
echo '<div>PHP Version: ' . PHP_VERSION . '</div>';
echo '<div>Server: ' . $_SERVER['SERVER_SOFTWARE'] . '</div>';

echo '<h2>7. Required PHP Extensions</h2>';
$extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'curl', 'mbstring', 'gd'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo '<div class="success">✓ ' . $ext . '</div>';
    } else {
        echo '<div class="error">✗ ' . $ext . ' NOT LOADED</div>';
    }
}

echo '<hr><p><strong>Next steps:</strong></p>';
echo '<ul>';
echo '<li>If all checks pass, try accessing: <a href="' . BASE_PATH . 'admin/login.php">' . BASE_PATH . 'admin/login.php</a></li>';
echo '<li>If you see errors above, those need to be fixed first</li>';
echo '<li>Check your server error logs for more details</li>';
echo '</ul>';
?>
