<?php
/**
 * Test only the functions called by header.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Header Functions</h1>";
echo "<pre>";

try {
    echo "Loading config...\n";
    require_once __DIR__ . '/config.php';

    echo "Loading Database...\n";
    require_once __DIR__ . '/core/Database.php';

    echo "Loading Cache...\n";
    require_once __DIR__ . '/core/Cache.php';

    echo "Loading Template...\n";
    require_once __DIR__ . '/core/Template.php';

    echo "Initializing...\n";
    $db = Database::getInstance();
    $cache = new Cache();
    Template::init();
    echo "✓ All initialized\n\n";

    // Test each function that header.php calls
    echo "Testing seo_title()...\n";
    try {
        $result = seo_title();
        echo "✓ seo_title() = '" . htmlspecialchars($result) . "'\n\n";
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    } catch (Error $e) {
        echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }

    echo "Testing site_name()...\n";
    try {
        $result = site_name();
        echo "✓ site_name() = '" . htmlspecialchars($result) . "'\n\n";
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    } catch (Error $e) {
        echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }

    echo "Testing site_tagline()...\n";
    try {
        $result = site_tagline();
        echo "✓ site_tagline() = '" . htmlspecialchars($result) . "'\n\n";
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    } catch (Error $e) {
        echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }

    echo "Testing theme_url()...\n";
    try {
        $result = theme_url('/style.css');
        echo "✓ theme_url('/style.css') = '" . htmlspecialchars($result) . "'\n\n";
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    } catch (Error $e) {
        echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }

    echo "Testing wp_head()...\n";
    try {
        ob_start();
        wp_head();
        $output = ob_get_clean();
        echo "✓ wp_head() produced " . strlen($output) . " bytes\n";
        echo "Output:\n" . htmlspecialchars($output) . "\n\n";
    } catch (Exception $e) {
        ob_end_clean();
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    } catch (Error $e) {
        ob_end_clean();
        echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }

    echo "\n=== ALL TESTS COMPLETE ===\n";
    echo "If all tests passed, the issue is elsewhere.\n";
    echo "If any test failed, that's the problem!\n";

} catch (Exception $e) {
    echo "\n\nFATAL ERROR DURING SETUP:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n\nFATAL ERROR DURING SETUP:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
