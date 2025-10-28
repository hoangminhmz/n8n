<?php
/**
 * Debug Topic Generation - Find exact error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120);

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AutoBlog/Campaign.php';

$db = Database::getInstance();

echo "<h1>🔍 Debug Topic Generation</h1>";

// Get campaign ID from URL
$campaignId = $_GET['id'] ?? null;

if (!$campaignId) {
    echo "<h2>Select a Campaign to Test:</h2>";
    $campaigns = $db->query("SELECT id, name, ai_provider, ai_model FROM campaigns ORDER BY id DESC");

    if (empty($campaigns)) {
        echo "<p>No campaigns found. <a href='campaigns.php'>Create one first</a></p>";
        exit;
    }

    echo "<ul>";
    foreach ($campaigns as $c) {
        echo "<li>";
        echo "<strong>" . htmlspecialchars($c->name) . "</strong> ";
        echo "(" . htmlspecialchars($c->ai_provider) . " / " . htmlspecialchars($c->ai_model) . ") ";
        echo "<a href='?id=" . $c->id . "' style='background: #667eea; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;'>Test This Campaign</a>";
        echo "</li>";
    }
    echo "</ul>";
    exit;
}

// Test topic generation for selected campaign
echo "<h2>Testing Campaign #$campaignId</h2>";

try {
    $campaign = $db->queryOne("SELECT * FROM campaigns WHERE id = ?", [$campaignId]);

    if (!$campaign) {
        throw new Exception("Campaign not found");
    }

    echo "<div style='background: #f0f0f0; padding: 15px; margin: 15px 0; border-radius: 4px;'>";
    echo "<h3>Campaign Details:</h3>";
    echo "<strong>Name:</strong> " . htmlspecialchars($campaign->name) . "<br>";
    echo "<strong>AI Provider:</strong> " . htmlspecialchars($campaign->ai_provider) . "<br>";
    echo "<strong>AI Model:</strong> <code style='background: #fff; padding: 2px 6px;'>" . htmlspecialchars($campaign->ai_model) . "</code><br>";
    echo "<strong>Niche:</strong> " . htmlspecialchars($campaign->niche) . "<br>";
    echo "<strong>Seed Keywords:</strong> " . htmlspecialchars($campaign->seed_keywords) . "<br>";
    echo "</div>";

    // Check if model is valid
    $validModels = ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.5-flash-lite', 'gemini-2.0-flash-exp', 'gemini-1.5-flash', 'gemini-1.5-pro'];

    if (!in_array($campaign->ai_model, $validModels)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;'>";
        echo "⚠️ <strong>Warning:</strong> Model '<code>" . htmlspecialchars($campaign->ai_model) . "</code>' may be deprecated or invalid!<br>";
        echo "Valid models: " . implode(', ', $validModels) . "<br>";
        echo "<a href='fix-gemini-models.php' style='color: #667eea;'>→ Run migration tool to fix</a>";
        echo "</div>";
    }

    echo "<hr>";
    echo "<h3>Step 1: Initializing Campaign Manager...</h3>";
    flush();

    $campaignManager = new Campaign();
    echo "✓ Campaign Manager created<br>";
    flush();

    echo "<h3>Step 2: Generating 5 topics...</h3>";
    echo "<p><em>This may take 5-10 seconds...</em></p>";
    flush();

    $startTime = microtime(true);

    $topics = $campaignManager->generateTopics($campaignId, 5);

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0;'>";
    echo "✅ <strong>Success!</strong> Generated " . count($topics) . " topics in {$duration} seconds";
    echo "</div>";

    echo "<h3>Generated Topics:</h3>";
    echo "<ol style='line-height: 1.8;'>";
    foreach ($topics as $topic) {
        echo "<li>" . htmlspecialchars($topic) . "</li>";
    }
    echo "</ol>";

    echo "<hr>";
    echo "<p><a href='campaigns.php?action=view&id=$campaignId'>← Back to Campaign</a></p>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0;'>";
    echo "<h3 style='margin-top: 0; color: #721c24;'>❌ Error Caught!</h3>";
    echo "<strong>Error Message:</strong><br>";
    echo "<code style='background: #fff; padding: 10px; display: block; margin: 10px 0;'>" . htmlspecialchars($e->getMessage()) . "</code>";

    echo "<strong>Error Location:</strong><br>";
    echo "<code style='background: #fff; padding: 10px; display: block; margin: 10px 0;'>";
    echo htmlspecialchars($e->getFile()) . ":" . $e->getLine();
    echo "</code>";

    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre style='background: #fff; padding: 10px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";

    echo "<hr>";
    echo "<h3>💡 Possible Solutions:</h3>";
    echo "<ul>";
    echo "<li>If error mentions model name: <a href='fix-gemini-models.php'>Run migration tool</a></li>";
    echo "<li>If API key error: Check config.php for GEMINI_API_KEY</li>";
    echo "<li>If timeout: Try generating fewer topics (3-5 instead of 10-20)</li>";
    echo "<li>If JSON parse error: Check AI response format in error message</li>";
    echo "</ul>";
}
?>
