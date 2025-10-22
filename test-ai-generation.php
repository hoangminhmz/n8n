<?php
/**
 * Test AI Content Generation
 * This script allows you to manually test the AI content generation process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AutoBlog/Campaign.php';
require_once SITE_PATH . '/core/AI/ContentGenerator.php';

$db = Database::getInstance();
$campaignManager = new Campaign();
$message = '';
$generatedPost = null;

// Get all campaigns
$campaigns = $db->query("SELECT * FROM campaigns ORDER BY created_at DESC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $campaignId = $_POST['campaign_id'];
    $topic = $_POST['topic'];

    try {
        echo "<pre style='background:#f5f5f5;padding:20px;border:1px solid #ddd;margin:20px 0;'>";
        echo "=== AI CONTENT GENERATION TEST ===\n\n";

        // 1. Get campaign details
        echo "Step 1: Loading campaign...\n";
        $campaign = $campaignManager->get($campaignId);
        if (!$campaign) {
            throw new Exception("Campaign not found!");
        }
        echo "✓ Campaign: {$campaign->name}\n";
        echo "✓ AI Provider: {$campaign->ai_provider}\n";
        echo "✓ AI Model: {$campaign->ai_model}\n\n";

        // 2. Create or get queue item
        echo "Step 2: Creating queue item...\n";
        $queueId = $db->insert('ai_queue', [
            'campaign_id' => $campaignId,
            'topic' => $topic,
            'keywords' => json_encode(['primary' => [$topic]]),
            'status' => 'pending',
            'priority' => 10,
            'scheduled_for' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        echo "✓ Queue ID: {$queueId}\n";
        echo "✓ Topic: {$topic}\n\n";

        // 3. Initialize content generator
        echo "Step 3: Initializing AI content generator...\n";
        $generator = new ContentGenerator();
        echo "✓ Content generator ready\n\n";

        // 4. Generate content
        echo "Step 4: Generating content with AI...\n";
        echo "(This may take 30-60 seconds...)\n\n";

        $startTime = microtime(true);

        $content = $generator->generate($campaign, [
            'topic' => $topic,
            'keywords' => [$topic],
            'word_count_min' => $campaign->word_count_min,
            'word_count_max' => $campaign->word_count_max
        ]);

        $duration = round(microtime(true) - $startTime, 2);

        if (!$content) {
            throw new Exception("AI generation failed! Check your API keys in Settings.");
        }

        echo "✓ Content generated successfully!\n";
        echo "✓ Generation time: {$duration} seconds\n";
        echo "✓ Content length: " . strlen($content['content']) . " characters\n";
        echo "✓ Word count: " . str_word_count($content['content']) . " words\n\n";

        // 5. Create post
        echo "Step 5: Creating post in database...\n";

        // Generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $content['title']), '-'));
        $slug = substr($slug, 0, 200); // Limit length

        $postData = [
            'title' => $content['title'],
            'slug' => $slug,
            'content' => $content['content'],
            'excerpt' => $content['excerpt'] ?? substr(strip_tags($content['content']), 0, 160),
            'status' => 'draft', // Save as draft for review
            'campaign_id' => $campaignId,
            'author_id' => 1,
            'is_ai_generated' => 1,
            'seo_title' => $content['seo_title'] ?? $content['title'],
            'meta_description' => $content['meta_description'] ?? '',
            'keywords' => json_encode(['primary' => [$topic]]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $postId = $db->insert('posts', $postData);
        echo "✓ Post created with ID: {$postId}\n";
        echo "✓ Status: DRAFT (review before publishing)\n\n";

        // 6. Update queue
        echo "Step 6: Updating queue status...\n";
        $db->update('ai_queue', [
            'status' => 'completed',
            'generated_post_id' => $postId,
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $queueId]);
        echo "✓ Queue item marked as completed\n\n";

        echo "=== GENERATION COMPLETE ===\n";
        echo "\nPost ID: {$postId}\n";
        echo "View post: <a href='" . BASE_PATH . "admin/posts.php?action=edit&id={$postId}'>Edit Post</a>\n";
        echo "</pre>";

        // Load the generated post for preview
        $generatedPost = $db->queryOne("SELECT * FROM posts WHERE id = ?", [$postId]);
        $message = "✓ Content generated successfully! Post ID: {$postId}";

    } catch (Exception $e) {
        echo "\n\n=== ERROR ===\n";
        echo "✗ " . $e->getMessage() . "\n";
        echo "\nFile: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
        echo "</pre>";
        $message = "✗ Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test AI Content Generation - LightBlog</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .alert-success { background: #d4edda; border-color: #28a745; color: #155724; }
        .alert-error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .alert-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .alert-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        select, input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea { min-height: 100px; }
        .btn {
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .post-preview {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .post-preview h2 { margin-bottom: 15px; color: #333; }
        .post-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .post-content {
            line-height: 1.8;
            color: #333;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Test AI Content Generation</h1>
        <p class="subtitle">Manually trigger AI content generation to test your setup</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= strpos($message, '✗') !== false ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>📋 How to use:</strong>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Select a campaign (must have AI API keys configured)</li>
                <li>Enter a topic for the article</li>
                <li>Click "Generate Content" and wait (30-60 seconds)</li>
                <li>Review the generated post (saved as DRAFT)</li>
                <li>Edit and publish in Posts section</li>
            </ol>
        </div>

        <div class="alert alert-warning">
            <strong>⚠️ Prerequisites:</strong><br>
            • Configure AI API keys in <a href="<?= BASE_PATH ?>admin/settings.php">Settings</a><br>
            • Create at least one campaign<br>
            • Make sure your server can make external API calls
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Select Campaign *</label>
                <select name="campaign_id" required>
                    <option value="">-- Choose a campaign --</option>
                    <?php foreach ($campaigns as $camp): ?>
                        <option value="<?= $camp->id ?>">
                            <?= htmlspecialchars($camp->name) ?>
                            (<?= $camp->ai_provider ?>/<?= $camp->ai_model ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Article Topic *</label>
                <input type="text" name="topic" placeholder="e.g., Best Laptops for Programming in 2025" required>
                <small style="color: #666;">Be specific for better results</small>
            </div>

            <button type="submit" name="generate" class="btn btn-success">
                🚀 Generate Content with AI
            </button>
        </form>

        <?php if ($generatedPost): ?>
            <div class="post-preview">
                <h2>📝 Generated Content Preview</h2>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-value"><?= $generatedPost->id ?></div>
                        <div class="stat-label">Post ID</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= str_word_count($generatedPost->content) ?></div>
                        <div class="stat-label">Words</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= strlen($generatedPost->content) ?></div>
                        <div class="stat-label">Characters</div>
                    </div>
                </div>

                <h3><?= htmlspecialchars($generatedPost->title) ?></h3>
                <div class="post-meta">
                    Status: <code><?= $generatedPost->status ?></code> |
                    Created: <?= $generatedPost->created_at ?> |
                    AI Generated: <?= $generatedPost->is_ai_generated ? 'Yes' : 'No' ?>
                </div>

                <div class="post-content">
                    <?= nl2br(htmlspecialchars(substr($generatedPost->content, 0, 500))) ?>
                    <?php if (strlen($generatedPost->content) > 500): ?>
                        <p><em>... (showing first 500 characters)</em></p>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <a href="<?= BASE_PATH ?>admin/posts.php?action=edit&id=<?= $generatedPost->id ?>"
                       class="btn">📝 Edit Full Post</a>
                    <a href="<?= BASE_PATH ?>admin/posts.php"
                       class="btn">📋 View All Posts</a>
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px;">
            <h3>💡 Tips for Better Results:</h3>
            <ul style="margin-left: 20px; margin-top: 10px; color: #666;">
                <li>Use specific, detailed topics (e.g., "Best Gaming Laptops Under $1000 in 2025")</li>
                <li>Include keywords naturally in the topic</li>
                <li>Test with different AI providers/models to compare quality</li>
                <li>Adjust campaign settings (temperature, word count) for different styles</li>
                <li>Review and edit AI content before publishing</li>
            </ul>
        </div>

        <div style="margin-top: 20px; text-align: center; color: #999;">
            <p><a href="<?= BASE_PATH ?>admin/">← Back to Admin Dashboard</a></p>
        </div>
    </div>
</body>
</html>
