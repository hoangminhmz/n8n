<?php
/**
 * Test AI-powered topic generation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/AutoBlog/Campaign.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AI Topic Generation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 2rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #3b82f6;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }
        select, input[type="text"], input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        button:hover {
            background: #2563eb;
        }
        .result {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 4px;
            border-left: 4px solid #3b82f6;
        }
        .success {
            color: #059669;
            border-left-color: #059669;
        }
        .error {
            color: #dc2626;
            border-left-color: #dc2626;
            background: #fef2f2;
        }
        .topic-list {
            margin-top: 1rem;
        }
        .topic-item {
            padding: 0.75rem;
            margin: 0.5rem 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .topic-number {
            background: #3b82f6;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .info {
            background: #eff6ff;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .meta-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test AI Topic Generation</h1>

        <div class="info">
            <strong>ℹ️ How this works:</strong><br>
            This tool tests the new AI-powered topic generation. It uses your campaign's AI provider (OpenAI, Claude, or Gemini) to generate intelligent, diverse, and SEO-optimized blog topics based on your seed keywords, niche, and campaign goals.
        </div>

        <form method="POST" id="testForm">
            <div class="form-group">
                <label>Select Campaign:</label>
                <select name="campaign_id" required>
                    <option value="">-- Choose a campaign --</option>
                    <?php
                    $campaignManager = new Campaign();
                    $campaigns = $campaignManager->getAll();
                    foreach ($campaigns as $camp) {
                        echo "<option value='{$camp->id}'>{$camp->name} ({$camp->niche})</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Number of Topics to Generate:</label>
                <input type="number" name="count" value="10" min="1" max="50" required>
            </div>

            <button type="submit">🚀 Generate Topics with AI</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campaignId = $_POST['campaign_id'] ?? null;
            $count = (int)($_POST['count'] ?? 10);

            if ($campaignId) {
                echo '<div class="result" id="result">';
                echo '<div style="display: flex; align-items: center;"><div class="loading"></div> Generating topics with AI...</div>';
                echo '</div>';
                echo '<script>document.getElementById("result").scrollIntoView({behavior: "smooth"});</script>';

                flush();
                ob_flush();

                try {
                    $startTime = microtime(true);
                    $campaignManager = new Campaign();

                    // Get campaign info
                    $campaign = $campaignManager->get($campaignId);

                    echo '<script>
                        document.getElementById("result").innerHTML = `
                            <div style="display: flex; align-items: center;"><div class="loading"></div> Using ' . htmlspecialchars($campaign->ai_provider) . ' (' . htmlspecialchars($campaign->ai_model) . ') to generate ' . $count . ' topics...</div>
                        `;
                    </script>';
                    flush();
                    ob_flush();

                    // Generate topics
                    $topics = $campaignManager->generateTopics($campaignId, $count);

                    $duration = round(microtime(true) - $startTime, 2);

                    if (!empty($topics)) {
                        echo '<script>document.getElementById("result").className = "result success";</script>';
                        echo '<script>document.getElementById("result").innerHTML = `';
                        echo '<h3>✅ Successfully generated ' . count($topics) . ' topics!</h3>';

                        echo '<div class="meta-info">';
                        echo '<strong>Campaign:</strong> ' . htmlspecialchars($campaign->name) . '<br>';
                        echo '<strong>Niche:</strong> ' . htmlspecialchars($campaign->niche) . '<br>';
                        echo '<strong>Goal:</strong> ' . htmlspecialchars($campaign->goal) . '<br>';
                        echo '<strong>AI Provider:</strong> ' . htmlspecialchars($campaign->ai_provider) . ' (' . htmlspecialchars($campaign->ai_model) . ')<br>';
                        echo '<strong>Seed Keywords:</strong> ' . htmlspecialchars(implode(', ', json_decode($campaign->seed_keywords, true) ?? [])) . '<br>';
                        echo '<strong>Generation Time:</strong> ' . $duration . ' seconds';
                        echo '</div>';

                        echo '<div class="topic-list">';
                        foreach ($topics as $index => $topic) {
                            echo '<div class="topic-item">';
                            echo '<div class="topic-number">' . ($index + 1) . '</div>';
                            echo '<div>' . htmlspecialchars($topic) . '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '`;</script>';
                    } else {
                        echo '<script>document.getElementById("result").className = "result error";</script>';
                        echo '<script>document.getElementById("result").innerHTML = `';
                        echo '<h3>❌ No topics generated</h3>';
                        echo '<p>The AI did not return any topics. Check your API key configuration and error logs.</p>';
                        echo '`;</script>';
                    }
                } catch (Exception $e) {
                    echo '<script>document.getElementById("result").className = "result error";</script>';
                    echo '<script>document.getElementById("result").innerHTML = `';
                    echo '<h3>❌ Error</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280;">If AI generation fails, the system will automatically fall back to template-based topic generation.</p>';
                    echo '`;</script>';
                }
            }
        }
        ?>
    </div>
</body>
</html>
