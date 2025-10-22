<?php
/**
 * Test Template functions directly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Template Functions Test</title></head><body>";
echo "<h1>Testing Template Functions</h1>";

try {
    // Load config
    echo "<h2>Step 1: Loading Config</h2>";
    require_once __DIR__ . '/config.php';
    echo "<p style='color:green'>✓ Config loaded</p>";

    // Load Database
    echo "<h2>Step 2: Loading Database</h2>";
    require_once __DIR__ . '/core/Database.php';
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Database loaded</p>";

    // Load Template
    echo "<h2>Step 3: Loading Template Class</h2>";
    require_once __DIR__ . '/core/Template.php';
    Template::init();
    echo "<p style='color:green'>✓ Template class loaded and initialized</p>";

    // Test each function individually
    echo "<h2>Step 4: Testing Template Functions (No Post Set)</h2>";
    echo "<div style='margin-left: 20px;'>";

    echo "<h3>Testing site_name()</h3>";
    try {
        ob_start();
        $result = Template::site_name();
        $output = ob_get_clean();
        echo "<p style='color:green'>✓ site_name() = '" . htmlspecialchars($result) . "'</p>";
        if ($output) {
            echo "<p style='color:orange'>⚠ Produced output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color:red'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    }

    echo "<h3>Testing seo_title()</h3>";
    try {
        ob_start();
        $result = Template::seo_title();
        $output = ob_get_clean();
        echo "<p style='color:green'>✓ seo_title() = '" . htmlspecialchars($result) . "'</p>";
        if ($output) {
            echo "<p style='color:orange'>⚠ Produced output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color:red'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    }

    echo "<h3>Testing meta_description()</h3>";
    try {
        ob_start();
        $result = Template::meta_description();
        $output = ob_get_clean();
        echo "<p style='color:green'>✓ meta_description() = '" . htmlspecialchars($result) . "'</p>";
        if ($output) {
            echo "<p style='color:orange'>⚠ Produced output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color:red'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    }

    echo "<h3>Testing the_title()</h3>";
    try {
        ob_start();
        $result = Template::the_title();
        $output = ob_get_clean();
        echo "<p style='color:green'>✓ the_title() = '" . htmlspecialchars($result) . "'</p>";
        if ($output) {
            echo "<p style='color:orange'>⚠ Produced output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color:red'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    }

    echo "<h3>Testing the_permalink()</h3>";
    try {
        ob_start();
        $result = Template::the_permalink();
        $output = ob_get_clean();
        echo "<p style='color:green'>✓ the_permalink() = '" . htmlspecialchars($result) . "'</p>";
        if ($output) {
            echo "<p style='color:orange'>⚠ Produced output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color:red'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    }

    echo "</div>";

    // Check Template.php file for null checks
    echo "<h2>Step 5: Checking Template.php for Null Safety</h2>";
    $template_file = __DIR__ . '/core/Template.php';
    $template_content = file_get_contents($template_file);

    // Check if null checks are present
    $has_null_check_title = strpos($template_content, 'if (!self::$current_post) return') !== false;
    $has_null_check_seo = strpos($template_content, 'if (!self::$current_post)') !== false;

    if ($has_null_check_title || $has_null_check_seo) {
        echo "<p style='color:green'>✓ Template.php HAS null safety checks</p>";
    } else {
        echo "<p style='color:red'>✗ Template.php MISSING null safety checks - File not updated!</p>";
        echo "<p>Please upload the fixed Template.php file to the server.</p>";
    }

    // Show relevant code from Template.php
    echo "<h3>Current seo_title() function:</h3>";
    if (preg_match('/public static function seo_title\(\).*?\n    \}/s', $template_content, $matches)) {
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    }

} catch (Exception $e) {
    echo "<div style='color:red;border:2px solid red;padding:10px;'>";
    echo "<h2>Fatal Error</h2>";
    echo "<p>Message: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</body></html>";
