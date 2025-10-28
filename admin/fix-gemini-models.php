<?php
/**
 * Fix Invalid Gemini Model Names in Campaigns
 * Updates campaigns using deprecated model names to valid ones
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';

$db = Database::getInstance();

echo "<h1>🔧 Fix Gemini Model Names in Campaigns</h1>";

// Map old invalid names to new valid ones
$modelFixes = [
    'gemini-flash-latest' => 'gemini-1.5-flash',
    'gemini-1.5-pro-latest' => 'gemini-1.5-pro',
    'gemini-2.5-flash' => 'gemini-1.5-flash',
    'gemini-pro' => 'gemini-1.5-pro',
];

echo "<h2>Step 1: Checking campaigns...</h2>";

$campaigns = $db->query("SELECT id, name, ai_provider, ai_model FROM campaigns");

if (empty($campaigns)) {
    echo "<p>✓ No campaigns found.</p>";
    exit;
}

echo "<p>Found " . count($campaigns) . " campaigns.</p>";

echo "<h2>Step 2: Finding campaigns with invalid Gemini models...</h2>";

$needsUpdate = [];
foreach ($campaigns as $campaign) {
    if ($campaign->ai_provider === 'gemini' && isset($modelFixes[$campaign->ai_model])) {
        $needsUpdate[] = [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'old_model' => $campaign->ai_model,
            'new_model' => $modelFixes[$campaign->ai_model]
        ];
    }
}

if (empty($needsUpdate)) {
    echo "<p style='color: green;'>✅ All campaigns are using valid model names!</p>";
    exit;
}

echo "<p style='color: orange;'>⚠️ Found " . count($needsUpdate) . " campaigns with invalid model names:</p>";

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Campaign ID</th><th>Name</th><th>Old Model</th><th>New Model</th><th>Status</th></tr>";

foreach ($needsUpdate as $item) {
    echo "<tr>";
    echo "<td>" . $item['id'] . "</td>";
    echo "<td>" . htmlspecialchars($item['name']) . "</td>";
    echo "<td><code style='color: red;'>" . htmlspecialchars($item['old_model']) . "</code></td>";
    echo "<td><code style='color: green;'>" . htmlspecialchars($item['new_model']) . "</code></td>";

    try {
        $db->query(
            "UPDATE campaigns SET ai_model = ? WHERE id = ?",
            [$item['new_model'], $item['id']]
        );
        echo "<td style='color: green;'>✅ Updated</td>";
    } catch (Exception $e) {
        echo "<td style='color: red;'>❌ Failed: " . htmlspecialchars($e->getMessage()) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";

echo "<h2>✅ Migration Complete!</h2>";
echo "<p>All campaigns have been updated to use valid Gemini model names.</p>";
echo "<p><a href='campaigns.php'>← Back to Campaigns</a></p>";
?>
