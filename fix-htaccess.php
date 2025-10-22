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
    // Read current .htaccess or create new one
    $htaccessPath = __DIR__ . '/.htaccess';

    $htaccessContent = '# LightBlog CMS - Apache Configuration

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase ' . $basePath . '

    # Force HTTPS (uncomment in production)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Deny access to sensitive files
    RewriteRule ^config\.php$ - [F,L]
    RewriteRule ^content/database/ - [F,L]
    RewriteRule ^content/cache/ - [F,L]

    # Allow admin panel access
    RewriteCond %{REQUEST_URI} ^/admin [OR]
    RewriteCond %{REQUEST_URI} ^.*/admin
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]

    # Allow access to static files and existing files
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Route everything else through index.php
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
</IfModule>

# Disable directory browsing
Options -Indexes

# PHP Settings (if allowed)
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value memory_limit 256M
    php_value max_execution_time 300
</IfModule>
';

    if (file_exists($htaccessPath)) {
        echo '<p>Found existing .htaccess file. Updating RewriteBase...</p>';
        $content = file_get_contents($htaccessPath);

        // Replace RewriteBase
        $newContent = preg_replace(
            '/RewriteBase\s+\/.*$/m',
            'RewriteBase ' . $basePath,
            $content
        );
    } else {
        echo '<p>No .htaccess file found. Creating new one...</p>';
        $newContent = $htaccessContent;
    }

    // Write .htaccess
    if (file_put_contents($htaccessPath, $newContent)) {
        echo '<div class="success">✅ .htaccess created/updated successfully!</div>';
        echo '<p>RewriteBase set to: <strong>' . htmlspecialchars($basePath) . '</strong></p>';

        echo '<h3>Current .htaccess content:</h3>';
        echo '<pre>' . htmlspecialchars($newContent) . '</pre>';

        echo '<a href="' . SITE_URL . '" class="btn">View Homepage →</a>';
        echo '<a href="' . BASE_PATH . 'admin/" class="btn">Go to Admin →</a>';
    } else {
        echo '<div class="error">❌ Failed to write .htaccess file.</div>';
        echo '<p><strong>Reason:</strong> PHP doesn\'t have permission to write to this directory.</p>';
        echo '<h3>Solution - Create .htaccess manually:</h3>';
        echo '<p>1. Using FTP or File Manager, create a new file named <code>.htaccess</code> in your <code>/lite</code> directory</p>';
        echo '<p>2. Copy and paste this content into the file:</p>';
        echo '<pre>' . htmlspecialchars($htaccessContent) . '</pre>';
        echo '<p>3. Save the file and refresh your homepage</p>';
    }
}

echo '    </div>
</body>
</html>';
?>
