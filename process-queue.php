<?php
/**
 * Manual Queue Processor
 * Run this to process pending queue items without waiting for cron
 */

require_once __DIR__ . '/config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AI/ContentGenerator.php';
require_once SITE_PATH . '/core/AutoBlog/SEOOptimizer.php';
require_once SITE_PATH . '/core/AutoBlog/LinkInjector.php';

// Check if running from web or CLI
$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    // Web interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Process Queue - LightBlog CMS</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f5f5f5;
                padding: 2rem;
            }
            .container {
                max-width: 1200px;
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
            .info {
                background: #eff6ff;
                padding: 1rem;
                border-radius: 4px;
                margin-bottom: 1.5rem;
                color: #1e40af;
                border-left: 4px solid #3b82f6;
            }
            .warning {
                background: #fffbeb;
                padding: 1rem;
                border-radius: 4px;
                margin-bottom: 1.5rem;
                color: #92400e;
                border-left: 4px solid #f59e0b;
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
            .log {
                margin-top: 2rem;
                padding: 1.5rem;
                background: #1f2937;
                color: #d1d5db;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 0.875rem;
                line-height: 1.6;
                max-height: 500px;
                overflow-y: auto;
            }
            .success {
                color: #10b981;
            }
            .error {
                color: #ef4444;
            }
            .step {
                color: #60a5fa;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            .stat-card {
                background: #f9fafb;
                padding: 1rem;
                border-radius: 4px;
                border-left: 4px solid #3b82f6;
            }
            .stat-label {
                font-size: 0.75rem;
                color: #6b7280;
                text-transform: uppercase;
                margin-bottom: 0.25rem;
            }
            .stat-value {
                font-size: 1.5rem;
                font-weight: 600;
                color: #111827;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚡ Manual Queue Processor</h1>

            <?php
            $db = Database::getInstance();

            // Get queue statistics
            $stats = $db->queryOne("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM ai_queue
            ");

            $pendingDue = $db->query("
                SELECT * FROM ai_queue
                WHERE status = 'pending'
                AND scheduled_for <= ?
                ORDER BY priority ASC, created_at ASC
                LIMIT 10
            ", [date('Y-m-d H:i:s')]);
            ?>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">Total Queue</div>
                    <div class="stat-value"><?= $stats->total ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value"><?= $stats->pending ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ready to Process</div>
                    <div class="stat-value"><?= count($pendingDue) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value"><?= $stats->completed ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Failed</div>
                    <div class="stat-value"><?= $stats->failed ?></div>
                </div>
            </div>

            <?php if (count($pendingDue) > 0): ?>
                <div class="info">
                    <strong>📋 Ready to Process:</strong><br>
                    Found <?= count($pendingDue) ?> queue items ready for processing (scheduled time has passed).
                    Click the button below to process them now.
                </div>

                <form method="POST">
                    <button type="submit" name="process_queue">🚀 Process Queue Now</button>
                </form>
            <?php else: ?>
                <div class="warning">
                    <strong>⏱️ No items ready to process</strong><br>
                    All pending items are scheduled for future times. They will be processed automatically when:
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li>The scheduled time arrives, AND</li>
                        <li>The cron job runs (or you manually process again)</li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_queue'])) {
                echo '<div class="log" id="log">';
                echo '<div class="step">🚀 Starting queue processing at ' . date('Y-m-d H:i:s') . '</div><br>';
                flush();
                ob_flush();

                $processed = processQueue();

                echo '<br><div class="step">✅ Queue processing completed at ' . date('Y-m-d H:i:s') . '</div>';
                echo '<div class="success">Processed ' . $processed . ' items</div>';
                echo '</div>';

                echo '<script>setTimeout(function(){ window.location.reload(); }, 3000);</script>';
            }
            ?>

            <div class="info" style="margin-top: 2rem;">
                <strong>💡 How this works:</strong><br>
                This tool manually processes queue items that are ready (scheduled time has passed).
                Normally, this happens automatically via cron job, but you can use this for instant processing.
                <br><br>
                <strong>📝 Next Steps:</strong>
                <ol style="margin: 0.5rem 0 0 1.5rem;">
                    <li>Set up cron job for automatic processing: <code>*/15 * * * * php <?= __DIR__ ?>/cron.php</code></li>
                    <li>Or visit <code><?= SITE_URL ?>/cron.php</code> periodically in your browser</li>
                    <li>Or use this page whenever you want to process queue manually</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    // CLI mode
    echo "Processing queue...\n\n";
    $processed = processQueue();
    echo "\nProcessed {$processed} items\n";
}

function processQueue() {
    $db = Database::getInstance();
    $processed = 0;

    // Get pending queue items (limit to 10 per run)
    $queueItems = $db->query("
        SELECT * FROM ai_queue
        WHERE status = 'pending'
        AND scheduled_for <= ?
        ORDER BY priority ASC, created_at ASC
        LIMIT 10
    ", [date('Y-m-d H:i:s')]);

    foreach ($queueItems as $item) {
        // Update status to processing
        $db->update('ai_queue', ['status' => 'processing'], 'id = :id', ['id' => $item->id]);

        try {
            echo '<div class="step">📝 Processing: ' . htmlspecialchars($item->topic) . '</div>';
            flush();
            ob_flush();

            // Get campaign
            $campaign = $db->queryOne("SELECT * FROM campaigns WHERE id = ?", [$item->campaign_id]);

            if (!$campaign) {
                throw new Exception('Campaign not found');
            }

            echo '<div>  ├─ Campaign: ' . htmlspecialchars($campaign->name) . '</div>';
            echo '<div>  ├─ AI: ' . htmlspecialchars($campaign->ai_provider) . ' (' . htmlspecialchars($campaign->ai_model) . ')</div>';
            flush();
            ob_flush();

            // Generate content
            echo '<div>  ├─ Generating content...</div>';
            flush();
            ob_flush();

            $generator = new ContentGenerator($campaign);
            $keywords = json_decode($item->keywords, true) ?? ['primary' => []];
            $article = $generator->generateArticle($item->topic, $keywords);

            echo '<div>  ├─ Optimizing SEO...</div>';
            flush();
            ob_flush();

            // Optimize SEO
            $seoOptimizer = new SEOOptimizer();
            $article['content'] = $seoOptimizer->optimize($article['content'], $keywords['primary'] ?? []);

            echo '<div>  ├─ Injecting affiliate links...</div>';
            flush();
            ob_flush();

            // Inject affiliate links
            $linkInjector = new LinkInjector();
            $article['content'] = $linkInjector->inject($article['content'], $campaign->id);

            echo '<div>  ├─ Saving post...</div>';
            flush();
            ob_flush();

            // Save post with all SEO fields
            $postId = $db->insert('posts', [
                'title' => $article['title'],
                'slug' => $article['slug'],
                'content' => $article['content'],
                'excerpt' => $article['excerpt'],
                'meta_description' => $article['meta_description'],
                'seo_title' => $article['seo_title'],
                'keywords' => $article['keywords'],
                'featured_image' => $article['featured_image'],
                'author_id' => 1, // Default to admin
                'status' => 'draft', // Save as draft for review
                'is_ai_generated' => 1,
                'campaign_id' => $campaign->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                // SEO Meta Fields
                'focus_keyword' => $article['focus_keyword'] ?? '',
                'canonical_url' => $article['canonical_url'] ?? '',
                'meta_robots' => $article['meta_robots'] ?? 'index,follow',
                // Open Graph
                'og_title' => $article['og_title'] ?? '',
                'og_description' => $article['og_description'] ?? '',
                'og_image' => $article['og_image'] ?? '',
                // Twitter Cards
                'twitter_title' => $article['twitter_title'] ?? '',
                'twitter_description' => $article['twitter_description'] ?? '',
                'twitter_image' => $article['twitter_image'] ?? '',
                // Schema
                'schema_type' => $article['schema_type'] ?? 'Article',
                'faq_data' => $article['faq_data'] ?? '',
                // Content Metrics
                'word_count' => $article['word_count'] ?? 0,
                'reading_time' => $article['reading_time'] ?? 0,
                'readability_score' => $article['readability_score'] ?? 0,
                'internal_links_count' => $article['internal_links_count'] ?? 0,
                'external_links_count' => $article['external_links_count'] ?? 0,
                'images_count' => $article['images_count'] ?? 0,
                'has_table_of_contents' => $article['has_table_of_contents'] ?? 0,
                // SEO Score
                'seo_score' => $article['seo_score'] ?? 0,
                'last_seo_check' => date('Y-m-d H:i:s')
            ]);

            // Update queue
            $db->update('ai_queue',
                [
                    'status' => 'completed',
                    'generated_post_id' => $postId,
                    'processed_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $item->id]
            );

            $processed++;
            echo '<div class="success">  ✓ Success! Created post ID: ' . $postId . '</div><br>';
            flush();
            ob_flush();

        } catch (Exception $e) {
            // Mark as failed
            $db->update('ai_queue',
                [
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $item->id]
            );

            echo '<div class="error">  ✗ Failed: ' . htmlspecialchars($e->getMessage()) . '</div><br>';
            flush();
            ob_flush();
        }
    }

    return $processed;
}
