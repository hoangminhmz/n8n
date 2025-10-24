<?php
/**
 * Test API configuration and image generation
 */

require_once __DIR__ . '/config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AI/ImageGenerator.php';

echo "=== API Configuration Test ===\n\n";

// Check API keys
echo "1. Checking API Keys:\n";
echo str_repeat('-', 80) . "\n";
echo "OPENAI_API_KEY: " . (defined('OPENAI_API_KEY') ? (empty(OPENAI_API_KEY) ? 'Defined but EMPTY ✗' : 'Configured ✓ (' . substr(OPENAI_API_KEY, 0, 8) . '...)') : 'Not defined ✗') . "\n";
echo "GEMINI_API_KEY: " . (defined('GEMINI_API_KEY') ? (empty(GEMINI_API_KEY) ? 'Defined but EMPTY ✗' : 'Configured ✓ (' . substr(GEMINI_API_KEY, 0, 8) . '...)') : 'Not defined ✗') . "\n";
echo "CLAUDE_API_KEY: " . (defined('CLAUDE_API_KEY') ? (empty(CLAUDE_API_KEY) ? 'Defined but EMPTY ✗' : 'Configured ✓ (' . substr(CLAUDE_API_KEY, 0, 8) . '...)') : 'Not defined ✗') . "\n";
echo str_repeat('-', 80) . "\n\n";

// Test ImageGenerator initialization
echo "2. Testing ImageGenerator Initialization:\n";
echo str_repeat('-', 80) . "\n";

try {
    $imageGen = new ImageGenerator('auto');
    echo "✓ ImageGenerator created successfully\n";

    // Use reflection to check which provider was selected
    $reflection = new ReflectionClass($imageGen);
    $providerProperty = $reflection->getProperty('provider');
    $providerProperty->setAccessible(true);
    $provider = $providerProperty->getValue($imageGen);

    $preferredProviderProperty = $reflection->getProperty('preferredProvider');
    $preferredProviderProperty->setAccessible(true);
    $preferredProvider = $preferredProviderProperty->getValue($imageGen);

    echo "✓ Preferred Provider: " . $preferredProvider . "\n";
    echo "✓ Active Provider Object: " . ($provider ? get_class($provider) : 'NULL (will use PHP GD)') . "\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo str_repeat('-', 80) . "\n\n";

// Test actual image generation
if (isset($argv[1]) && $argv[1] === '--test-generate') {
    echo "3. Testing Actual Image Generation:\n";
    echo str_repeat('-', 80) . "\n";

    $testTitle = "Beautiful Mountain Landscape with Lake";
    echo "Test Title: {$testTitle}\n\n";

    // Test with different providers
    $providers = ['auto', 'openai', 'gemini', 'php-gd'];

    foreach ($providers as $providerName) {
        echo "Testing with provider: {$providerName}\n";

        try {
            $imageGen = new ImageGenerator($providerName);

            $startTime = microtime(true);
            $result = $imageGen->generateThumbnail($testTitle, [
                'size' => '1200x630',
                'quality' => 'standard',
                'style' => 'professional'
            ]);
            $endTime = microtime(true);

            echo "  ✓ Success!\n";
            echo "  ✓ Provider Used: {$result['provider']}\n";
            echo "  ✓ Image URL: {$result['image_url']}\n";
            echo "  ✓ Cost: $" . number_format($result['cost'], 4) . "\n";
            echo "  ✓ Time: " . round($endTime - $startTime, 2) . "s\n";
            echo "\n";

        } catch (Exception $e) {
            echo "  ✗ Error: {$e->getMessage()}\n\n";
        }
    }

    echo str_repeat('-', 80) . "\n";
    echo "\nNOTE: Check the generated images at the URLs above to verify quality.\n";
    echo "OpenAI images should be REAL topical photos, not text overlays.\n";

} else {
    echo "3. To test actual image generation, run:\n";
    echo "   php test-api-config.php --test-generate\n";
}

echo "\n=== End of Test ===\n";
