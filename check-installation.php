<?php
/**
 * Quick diagnostic script - Check if Pages & Menu system files exist
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 LightBlog Pages & Menu System - Diagnostic</h1>";
echo "<style>
body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
.ok { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { background: white; border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background: #4CAF50; color: white; }
</style>";

$basePath = __DIR__;

echo "<h2>📂 File Checks</h2>";
echo "<table>";
echo "<tr><th>File</th><th>Status</th><th>Size</th><th>Modified</th></tr>";

$files = [
    'admin/pages.php' => 'Pages admin interface',
    'admin/menus.php' => 'Menus admin interface',
    'themes/default/page.php' => 'Page template',
    'install/migrate-pages.sql' => 'Pages migration',
    'install/migrate-menus.sql' => 'Menus migration',
    'core/Template.php' => 'Template engine (should be updated)',
    'index.php' => 'Main router (should be updated)',
];

foreach ($files as $file => $desc) {
    $fullPath = $basePath . '/' . $file;
    $exists = file_exists($fullPath);

    echo "<tr>";
    echo "<td><strong>$file</strong><br><small>$desc</small></td>";

    if ($exists) {
        $size = filesize($fullPath);
        $modified = date('Y-m-d H:i:s', filemtime($fullPath));
        echo "<td class='ok'>✅ EXISTS</td>";
        echo "<td>" . number_format($size) . " bytes</td>";
        echo "<td>$modified</td>";
    } else {
        echo "<td class='error'>❌ MISSING</td>";
        echo "<td colspan='2' class='error'>FILE NOT FOUND ON SERVER</td>";
    }

    echo "</tr>";
}

echo "</table>";

// Check if Template.php has menu functions
echo "<h2>🔍 Code Checks</h2>";
echo "<table>";
echo "<tr><th>Check</th><th>Status</th></tr>";

$checks = [];

// Check Template.php for menu functions
if (file_exists($basePath . '/core/Template.php')) {
    $templateContent = file_get_contents($basePath . '/core/Template.php');
    $checks['render_menu function'] = strpos($templateContent, 'function render_menu') !== false;
    $checks['setCurrentPage function'] = strpos($templateContent, 'function setCurrentPage') !== false;
} else {
    $checks['Template.php exists'] = false;
}

// Check index.php for page routing
if (file_exists($basePath . '/index.php')) {
    $indexContent = file_get_contents($basePath . '/index.php');
    $checks['Page routing in index.php'] = strpos($indexContent, 'setCurrentPage') !== false;
    $checks['Generic slug route'] = strpos($indexContent, 'Generic slug route') !== false;
} else {
    $checks['index.php exists'] = false;
}

// Check admin header for menu items
if (file_exists($basePath . '/admin/includes/header.php')) {
    $headerContent = file_get_contents($basePath . '/admin/includes/header.php');
    $checks['Pages menu item'] = strpos($headerContent, 'pages.php') !== false;
    $checks['Menus menu item'] = strpos($headerContent, 'menus.php') !== false;
} else {
    $checks['admin/includes/header.php exists'] = false;
}

foreach ($checks as $check => $passed) {
    echo "<tr>";
    echo "<td>$check</td>";
    if ($passed) {
        echo "<td class='ok'>✅ PASSED</td>";
    } else {
        echo "<td class='error'>❌ FAILED</td>";
    }
    echo "</tr>";
}

echo "</table>";

// Database checks
echo "<h2>🗄️ Database Checks</h2>";

if (file_exists($basePath . '/config.php')) {
    require_once $basePath . '/config.php';
    require_once $basePath . '/core/Database.php';

    try {
        $db = Database::getInstance();

        echo "<table>";
        echo "<tr><th>Table</th><th>Status</th><th>Rows</th></tr>";

        // Check pages table
        try {
            $count = $db->queryOne("SELECT COUNT(*) as count FROM pages");
            echo "<tr><td>pages</td><td class='ok'>✅ EXISTS</td><td>{$count->count} pages</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>pages</td><td class='error'>❌ NOT FOUND</td><td>Run migrate-pages.sql</td></tr>";
        }

        // Check menus table
        try {
            $count = $db->queryOne("SELECT COUNT(*) as count FROM menus");
            echo "<tr><td>menus</td><td class='ok'>✅ EXISTS</td><td>{$count->count} menus</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>menus</td><td class='error'>❌ NOT FOUND</td><td>Run migrate-menus.sql</td></tr>";
        }

        // Check menu_items table
        try {
            $count = $db->queryOne("SELECT COUNT(*) as count FROM menu_items");
            echo "<tr><td>menu_items</td><td class='ok'>✅ EXISTS</td><td>{$count->count} items</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>menu_items</td><td class='error'>❌ NOT FOUND</td><td>Run migrate-menus.sql</td></tr>";
        }

        echo "</table>";

    } catch (Exception $e) {
        echo "<p class='error'>Database connection error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ config.php not found - cannot check database</p>";
}

// Summary
echo "<h2>📋 Summary</h2>";

$allFilesExist = true;
foreach ($files as $file => $desc) {
    if (!file_exists($basePath . '/' . $file)) {
        $allFilesExist = false;
        break;
    }
}

if ($allFilesExist && array_filter($checks)) {
    echo "<p class='ok' style='font-size: 1.2em;'>✅ All files are present! Pages & Menu system is installed.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Clear browser cache (Ctrl + Shift + R)</li>";
    echo "<li>Logout and login to admin again</li>";
    echo "<li>Go to: <a href='admin/pages.php'>admin/pages.php</a></li>";
    echo "<li>Go to: <a href='admin/menus.php'>admin/menus.php</a></li>";
    echo "</ol>";
} else {
    echo "<p class='error' style='font-size: 1.2em;'>❌ Some files are missing!</p>";
    echo "<p><strong>Action required:</strong></p>";
    echo "<ol>";
    echo "<li>Pull latest code from git: <code>git pull origin claude/explore-source-code-011CUQRS3hVsmYk8z34DVBW2</code></li>";
    echo "<li>Or manually upload missing files via FTP</li>";
    echo "<li>Run database migrations (migrate-pages.sql and migrate-menus.sql)</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><small>Diagnostic script - Safe to delete after checking</small></p>";
?>
