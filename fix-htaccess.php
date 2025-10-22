<?php
/**
 * .htaccess Generator - Auto-configure RewriteBase for subdirectory installations
 */

require_once __DIR__ . '/config.php';

// Get BASE_PATH from config
$basePath = defined('BASE_PATH') ? BASE_PATH : '/';

echo '<!DOCTYPE html>
<html>
<head>
    <title>.htaccess Configuration</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; padding: 15px; border-radius: 4px; color: #155724; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 4px; color: #721c24; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 .htaccess Configuration</h1>';

if (!isset($_POST['generate'])) {
    echo '<p>Current BASE_PATH: <strong>' . htmlspecialchars($basePath) . '</strong></p>';
    echo '<p>This script will update your .htaccess file to use the correct RewriteBase for your installation directory.</p>';

    echo '<form method="POST">';
    echo '<button type="submit" name="generate" class="btn">Generate .htaccess</button>';
    echo '</form>';
} else {
    // Read current .htaccess
    $htaccessPath = __DIR__ . '/.htaccess';

    if (!file_exists($htaccessPath)) {
        echo '<div class="error">Error: .htaccess file not found!</div>';
        exit;
    }

    $content = file_get_contents($htaccessPath);

    // Replace RewriteBase
    $newContent = preg_replace(
        '/RewriteBase\s+\/.*$/m',
        'RewriteBase ' . $basePath,
        $content
    );

    // Write back
    if (file_put_contents($htaccessPath, $newContent)) {
        echo '<div class="success">✅ .htaccess updated successfully!</div>';
        echo '<p>RewriteBase set to: <strong>' . htmlspecialchars($basePath) . '</strong></p>';

        echo '<h3>Updated .htaccess content:</h3>';
        echo '<pre>' . htmlspecialchars($newContent) . '</pre>';

        echo '<a href="' . SITE_URL . '" class="btn">View Homepage →</a>';
        echo '<a href="' . BASE_PATH . 'admin/" class="btn">Go to Admin →</a>';
    } else {
        echo '<div class="error">❌ Failed to write .htaccess file. Please check file permissions.</div>';
    }
}

echo '    </div>
</body>
</html>';
?>
