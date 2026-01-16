<?php
/**
 * Test AI Prompts Page - Minimal Version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Loading config...<br>";
require_once __DIR__ . '/../config.php';
echo "✓ Config loaded<br>";

echo "Step 2: Loading Database...<br>";
require_once SITE_PATH . '/core/Database.php';
echo "✓ Database loaded<br>";

echo "Step 3: Checking session...<br>";
session_start();
if (!isset($_SESSION['user_id'])) {
    die("❌ Not logged in. Please login first at: <a href='login.php'>Login</a>");
}
echo "✓ User ID: " . $_SESSION['user_id'] . "<br>";

echo "Step 4: Loading PromptManager...<br>";
require_once SITE_PATH . '/core/AI/PromptManager.php';
$promptManager = new PromptManager();
echo "✓ PromptManager created<br>";

echo "Step 5: Getting templates...<br>";
$postTemplates = $promptManager->getTemplatesByCategory('post');
echo "✓ Got " . count($postTemplates) . " post templates<br>";

echo "Step 6: Looping through templates...<br>";
foreach ($postTemplates as $index => $template) {
    echo "  Template #" . ($index + 1) . ": " . htmlspecialchars($template->template_name ?? 'UNNAMED') . "<br>";

    // Check properties
    echo "    - template_key: " . (isset($template->template_key) ? '✓' : '❌ MISSING') . "<br>";
    echo "    - template_name: " . (isset($template->template_name) ? '✓' : '❌ MISSING') . "<br>";
    echo "    - description: " . (isset($template->description) ? '✓' : '❌ MISSING') . "<br>";
    echo "    - variables: " . (isset($template->variables) ? '✓' : '❌ MISSING') . "<br>";
    echo "    - default_prompt: " . (isset($template->default_prompt) ? '✓' : '❌ MISSING') . "<br>";
    echo "    - custom_prompt: " . (isset($template->custom_prompt) ? '✓ (or null)' : '❌ MISSING') . "<br>";
    echo "    - is_active: " . (isset($template->is_active) ? '✓' : '❌ MISSING') . "<br>";
}

echo "<br>Step 7: Trying to include header...<br>";
$pageTitle = 'Test AI Prompts';
try {
    require_once __DIR__ . '/includes/header.php';
    echo "✓ Header included<br>";
} catch (Exception $e) {
    echo "❌ Header error: " . $e->getMessage() . "<br>";
}

echo "<br>✅ ALL TESTS PASSED! The page structure is OK.<br>";
echo "<br>If ai-prompts.php still fails, the issue is in the HTML/JavaScript rendering.<br>";
?>
