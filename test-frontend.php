<?php
/**
 * Front-end Debug Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><title>Front-end Test</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}';
echo '.success{color:green;} .error{color:red;} pre{background:#fff;padding:10px;border:1px solid #ddd;}</style>';
echo '</head><body><h1>🔍 Front-end Debug Test</h1>';

echo '<h2>Step 1: Config Loading</h2>';
try {
    require_once __DIR__ . '/config.php';
    echo '<div class="success">✓ config.php loaded</div>';
    echo '<div>BASE_PATH: ' . BASE_PATH . '</div>';
    echo '<div>SITE_URL: ' . SITE_URL . '</div>';
    echo '<div>CURRENT_THEME: ' . CURRENT_THEME . '</div>';
} catch (Exception $e) {
    echo '<div class="error">✗ Error: ' . $e->getMessage() . '</div>';
    die();
}

echo '<h2>Step 2: Core Classes</h2>';
try {
    require_once SITE_PATH . '/core/Database.php';
    echo '<div class="success">✓ Database.php loaded</div>';

    require_once SITE_PATH . '/core/Cache.php';
    echo '<div class="success">✓ Cache.php loaded</div>';

    require_once SITE_PATH . '/core/Router.php';
    echo '<div class="success">✓ Router.php loaded</div>';

    require_once SITE_PATH . '/core/Template.php';
    echo '<div class="success">✓ Template.php loaded</div>';
} catch (Exception $e) {
    echo '<div class="error">✗ Error loading core classes: ' . $e->getMessage() . '</div>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
    die();
}

echo '<h2>Step 3: Database Connection</h2>';
try {
    $db = Database::getInstance();
    echo '<div class="success">✓ Database connected</div>';

    $postCount = $db->count('posts', 'status = ?', ['published']);
    echo '<div class="success">✓ Found ' . $postCount . ' published posts</div>';
} catch (Exception $e) {
    echo '<div class="error">✗ Database error: ' . $e->getMessage() . '</div>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<h2>Step 4: Theme Files Check</h2>';
$themeDir = SITE_PATH . '/themes/' . CURRENT_THEME;
$themeFiles = ['index.php', 'header.php', 'footer.php', 'single.php', '404.php', 'style.css'];

foreach ($themeFiles as $file) {
    $path = $themeDir . '/' . $file;
    if (file_exists($path)) {
        echo '<div class="success">✓ ' . $file . ' exists</div>';
    } else {
        echo '<div class="error">✗ ' . $file . ' MISSING</div>';
    }
}

echo '<h2>Step 5: Router Test</h2>';
try {
    $cache = new Cache();
    $router = new Router();
    Template::init();

    echo '<div class="success">✓ Router initialized</div>';

    $uri = Router::getCurrentUri();
    echo '<div>Current URI: ' . htmlspecialchars($uri) . '</div>';
    echo '<div>REQUEST_URI: ' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</div>';
} catch (Exception $e) {
    echo '<div class="error">✗ Router error: ' . $e->getMessage() . '</div>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<h2>Step 6: Test Loading Homepage Template</h2>';
try {
    $posts = $db->query("
        SELECT * FROM posts
        WHERE status = 'published'
        ORDER BY published_at DESC
        LIMIT 10
    ");
    echo '<div class="success">✓ Query successful: ' . count($posts) . ' posts retrieved</div>';

    if (count($posts) > 0) {
        echo '<div class="success">✓ Posts available for display:</div>';
        foreach ($posts as $post) {
            echo '<div style="margin-left:20px;">- ' . htmlspecialchars($post->title) . '</div>';
        }
    } else {
        echo '<div class="error">⚠ No published posts found. Create a post in admin panel.</div>';
    }

    echo '<h3>Attempting to load theme index.php:</h3>';
    $themePath = SITE_PATH . '/themes/' . CURRENT_THEME . '/index.php';
    if (file_exists($themePath)) {
        echo '<div class="success">✓ Theme file exists: ' . $themePath . '</div>';
        echo '<div>Attempting to include theme...</div>';
        echo '<hr style="margin:20px 0;"><div style="border:2px solid #667eea;padding:10px;">';
        include $themePath;
        echo '</div><hr style="margin:20px 0;">';
        echo '<div class="success">✓ Theme loaded successfully!</div>';
    } else {
        echo '<div class="error">✗ Theme file not found!</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">✗ Error loading homepage: ' . $e->getMessage() . '</div>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<h2>Step 7: .htaccess Check</h2>';
if (file_exists(__DIR__ . '/.htaccess')) {
    echo '<div class="success">✓ .htaccess exists</div>';
    echo '<h3>.htaccess content:</h3>';
    echo '<pre>' . htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess')) . '</pre>';
} else {
    echo '<div class="error">✗ .htaccess NOT FOUND</div>';
    echo '<p>Run <a href="fix-htaccess.php">fix-htaccess.php</a> to create it.</p>';
}

echo '<hr><h2>Summary</h2>';
echo '<p>If all checks pass above but homepage is still blank:</p>';
echo '<ul>';
echo '<li>Check if .htaccess is being read by Apache (check server config)</li>';
echo '<li>Check Apache error logs for more details</li>';
echo '<li>Try accessing: <a href="' . SITE_URL . '/test-frontend.php">test-frontend.php</a> directly</li>';
echo '<li>Try accessing: <a href="' . SITE_URL . '/index.php">index.php</a> directly</li>';
echo '</ul>';

echo '</body></html>';
?>
