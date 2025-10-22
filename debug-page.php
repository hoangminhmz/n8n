<?php
/**
 * Comprehensive Debug Script for Blank Page Issue
 * This will show exactly where the error occurs
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug Page</title>";
echo "<style>body{font-family:monospace;margin:20px;} .success{color:green;} .error{color:red;} .section{margin:20px 0;padding:10px;border:1px solid #ccc;}</style>";
echo "</head><body><h1>LightBlog Debug - Blank Page Investigation</h1>";

function debug_step($message, $success = true) {
    $class = $success ? 'success' : 'error';
    $icon = $success ? '✓' : '✗';
    echo "<div class='$class'>$icon $message</div>";
}

try {
    // Step 1: Load config
    echo "<div class='section'><h2>Step 1: Loading Configuration</h2>";
    if (!file_exists(__DIR__ . '/config.php')) {
        debug_step("config.php NOT FOUND", false);
        die();
    }
    require_once __DIR__ . '/config.php';
    debug_step("config.php loaded successfully");
    debug_step("SITE_URL: " . SITE_URL);
    debug_step("DB_TYPE: " . DB_TYPE);
    echo "</div>";

    // Step 2: Test database
    echo "<div class='section'><h2>Step 2: Testing Database</h2>";
    require_once __DIR__ . '/core/Database.php';
    $db = new Database();
    debug_step("Database connection successful");

    $posts = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")->fetch();
    debug_step("Found " . $posts['count'] . " published posts");
    echo "</div>";

    // Step 3: Load Template class
    echo "<div class='section'><h2>Step 3: Loading Template Class</h2>";
    require_once __DIR__ . '/core/Template.php';
    debug_step("Template class loaded");

    // Test Template functions with no post set
    ob_start();
    $title = Template::seo_title();
    $desc = Template::meta_description();
    $permalink = Template::the_permalink();
    $output = ob_get_clean();

    if ($output) {
        debug_step("WARNING: Template functions produced output: " . htmlspecialchars($output), false);
    } else {
        debug_step("Template functions work without errors");
    }

    debug_step("seo_title() returned: '" . htmlspecialchars($title) . "'");
    debug_step("meta_description() returned: '" . htmlspecialchars(substr($desc, 0, 50)) . "...'");
    debug_step("the_permalink() returned: '" . htmlspecialchars($permalink) . "'");
    echo "</div>";

    // Step 4: Test theme header
    echo "<div class='section'><h2>Step 4: Testing Theme Header</h2>";
    $theme = ACTIVE_THEME;
    $header_file = __DIR__ . '/themes/' . $theme . '/header.php';

    if (!file_exists($header_file)) {
        debug_step("Header file NOT FOUND: $header_file", false);
    } else {
        debug_step("Header file exists: $header_file");

        // Try to include header and catch any output/errors
        ob_start();
        try {
            include $header_file;
            $header_output = ob_get_clean();

            if (empty($header_output)) {
                debug_step("WARNING: Header produced NO OUTPUT", false);
            } else {
                debug_step("Header produced " . strlen($header_output) . " bytes of output");
                echo "<details><summary>View header output (first 500 chars)</summary><pre>";
                echo htmlspecialchars(substr($header_output, 0, 500));
                echo "</pre></details>";
            }
        } catch (Exception $e) {
            ob_end_clean();
            debug_step("ERROR in header.php: " . $e->getMessage(), false);
            debug_step("File: " . $e->getFile() . " Line: " . $e->getLine(), false);
        }
    }
    echo "</div>";

    // Step 5: Test theme index
    echo "<div class='section'><h2>Step 5: Testing Theme Index</h2>";
    $index_file = __DIR__ . '/themes/' . $theme . '/index.php';

    if (!file_exists($index_file)) {
        debug_step("Index file NOT FOUND: $index_file", false);
    } else {
        debug_step("Index file exists: $index_file");

        // Read the file content
        $index_content = file_get_contents($index_file);
        debug_step("Index file size: " . strlen($index_content) . " bytes");

        echo "<details><summary>View index.php content</summary><pre>";
        echo htmlspecialchars($index_content);
        echo "</pre></details>";

        // Try to include index
        ob_start();
        try {
            include $index_file;
            $index_output = ob_get_clean();

            if (empty($index_output)) {
                debug_step("WARNING: Index produced NO OUTPUT", false);
            } else {
                debug_step("Index produced " . strlen($index_output) . " bytes of output");
                echo "<details><summary>View index output (first 500 chars)</summary><pre>";
                echo htmlspecialchars(substr($index_output, 0, 500));
                echo "</pre></details>";
            }
        } catch (Exception $e) {
            ob_end_clean();
            debug_step("ERROR in index.php: " . $e->getMessage(), false);
            debug_step("File: " . $e->getFile() . " Line: " . $e->getLine(), false);
        }
    }
    echo "</div>";

    // Step 6: Check for PHP errors in log
    echo "<div class='section'><h2>Step 6: Checking PHP Error Log</h2>";
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        debug_step("Error log location: $error_log");
        $errors = file_get_contents($error_log);
        $recent_errors = array_slice(explode("\n", $errors), -20);
        echo "<details><summary>Last 20 error log entries</summary><pre>";
        echo htmlspecialchars(implode("\n", $recent_errors));
        echo "</pre></details>";
    } else {
        debug_step("No error log file found or configured");
    }
    echo "</div>";

    // Step 7: Test actual index.php
    echo "<div class='section'><h2>Step 7: Testing Actual index.php</h2>";
    $main_index = __DIR__ . '/index.php';
    debug_step("Main index.php exists: " . (file_exists($main_index) ? 'YES' : 'NO'));

    if (file_exists($main_index)) {
        $index_content = file_get_contents($main_index);
        debug_step("Main index.php size: " . strlen($index_content) . " bytes");

        echo "<details><summary>View main index.php content</summary><pre>";
        echo htmlspecialchars($index_content);
        echo "</pre></details>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>Fatal Error</h2>";
    echo "<div>Message: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div>File: " . htmlspecialchars($e->getFile()) . "</div>";
    echo "<div>Line: " . $e->getLine() . "</div>";
    echo "<div>Stack trace:</div><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div class='section'><h2>Summary</h2>";
echo "<p>If you see this message, PHP is executing correctly.</p>";
echo "<p>Please take a screenshot of this entire page and share it.</p>";
echo "</div>";

echo "</body></html>";
