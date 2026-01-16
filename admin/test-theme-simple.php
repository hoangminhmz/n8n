<?php
/**
 * Simple Theme Test - Minimal dependencies
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';

echo "<h1>Theme Generation Test</h1>";
echo "<pre>";

try {
    echo "Step 1: Loading Database... ";
    require_once SITE_PATH . '/core/Database.php';
    echo "✅\n";

    echo "Step 2: Loading Auth... ";
    require_once SITE_PATH . '/core/Auth.php';
    echo "✅\n";

    echo "Step 3: Checking session... ";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅\n";

    echo "Step 4: Loading AI Provider Factory... ";
    require_once SITE_PATH . '/core/AI/AIProviderFactory.php';
    echo "✅\n";

    echo "Step 5: Creating Gemini provider... ";
    $provider = AIProviderFactory::create('gemini');
    echo "✅\n";

    echo "Step 6: Testing AI call... ";
    $result = $provider->generate('Respond with only: {"test": "success"}', [
        'temperature' => 0.7,
        'max_tokens' => 100
    ]);
    echo "✅\n";

    echo "\nAI Response:\n";
    echo "================\n";
    echo $result['content'];
    echo "\n================\n\n";

    echo "Step 7: Testing JSON parse... ";
    $content = $result['content'];

    // Extract JSON
    $firstBrace = strpos($content, '{');
    $lastBrace = strrpos($content, '}');

    if ($firstBrace !== false && $lastBrace !== false) {
        $json = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
        $parsed = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅\n";
            echo "Parsed: " . print_r($parsed, true);
        } else {
            echo "❌ JSON Error: " . json_last_error_msg() . "\n";
            echo "Extracted: " . $json . "\n";
        }
    } else {
        echo "❌ No JSON found\n";
    }

    echo "\n✅ ALL TESTS PASSED!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
