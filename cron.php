<?php
/**
 * LightBlog CMS - Cron Job Handler
 * Run this file periodically (every 5-15 minutes) via:
 * - Server cron: */15 * * * * php /path/to/cron.php
 * - Web cron: curl https://yourdomain.com/cron.php
 * - Manual: Visit https://yourdomain.com/cron.php in browser
 */

require_once __DIR__ . '/config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AutoBlog/Scheduler.php';

// Security: Optional cron secret key
if (defined('CRON_SECRET_KEY') && CRON_SECRET_KEY) {
    if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
        die('Unauthorized');
    }
}

$db = Database::getInstance();
$results = [];

echo "LightBlog CMS - Cron Job Started at " . date('Y-m-d H:i:s') . "\n\n";

// 1. Publish Scheduled Posts
try {
    $scheduler = new Scheduler();
    $published = $scheduler->publishScheduled();
    $results[] = "✓ Published {$published} scheduled posts";
    echo $results[count($results) - 1] . "\n";
} catch (Exception $e) {
    $results[] = "✗ Error publishing posts: " . $e->getMessage();
    echo $results[count($results) - 1] . "\n";
}

// 2. Process AI Generation Queue
if (defined('AUTOBLOG_ENABLED') && AUTOBLOG_ENABLED) {
    try {
        require_once SITE_PATH . '/core/AI/ContentGenerator.php';
        require_once SITE_PATH . '/core/AutoBlog/SEOOptimizer.php';
        require_once SITE_PATH . '/core/AutoBlog/LinkInjector.php';

        // Get pending queue items (limit to 3 per run to avoid timeout)
        $queueItems = $db->query("
            SELECT * FROM ai_queue
            WHERE status = 'pending'
            AND scheduled_for <= ?
            ORDER BY priority ASC, created_at ASC
            LIMIT 3
        ", [date('Y-m-d H:i:s')]);

        $generated = 0;

        foreach ($queueItems as $item) {
            // Update status to processing
            $db->update('ai_queue', ['status' => 'processing'], 'id = :id', ['id' => $item->id]);

            try {
                // Get campaign
                $campaign = $db->queryOne("SELECT * FROM campaigns WHERE id = ?", [$item->campaign_id]);

                if (!$campaign) {
                    throw new Exception('Campaign not found');
                }

                // Generate content
                $generator = new ContentGenerator($campaign);
                $keywords = json_decode($item->keywords, true) ?? ['primary' => []];
                $article = $generator->generateArticle($item->topic, $keywords);

                // Optimize SEO
                $seoOptimizer = new SEOOptimizer();
                $article['content'] = $seoOptimizer->optimize($article['content'], $keywords['primary'] ?? []);

                // Inject affiliate links
                $linkInjector = new LinkInjector();
                $article['content'] = $linkInjector->inject($article['content'], $campaign->id);

                // Save post
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
                    'updated_at' => date('Y-m-d H:i:s')
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

                $generated++;
                echo "  ✓ Generated: " . $article['title'] . " (Post ID: {$postId})\n";

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

                echo "  ✗ Failed: " . $item->topic . " - " . $e->getMessage() . "\n";
            }
        }

        $results[] = "✓ Processed {$generated} AI generation tasks";
        echo $results[count($results) - 1] . "\n";

    } catch (Exception $e) {
        $results[] = "✗ Error processing AI queue: " . $e->getMessage();
        echo $results[count($results) - 1] . "\n";
    }
}

// 3. Clean expired cache
try {
    require_once SITE_PATH . '/core/Cache.php';
    $cache = new Cache();
    $cleared = $cache->clearExpired();
    $results[] = "✓ Cleared {$cleared} expired cache files";
    echo $results[count($results) - 1] . "\n";
} catch (Exception $e) {
    $results[] = "✗ Error clearing cache: " . $e->getMessage();
    echo $results[count($results) - 1] . "\n";
}

// Log cron execution
try {
    $db->insert('cron_logs', [
        'job_name' => 'main_cron',
        'status' => 'success',
        'message' => implode('; ', $results),
        'executed_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo "✗ Error logging cron execution: " . $e->getMessage() . "\n";
}

echo "\nCron Job Completed at " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 50) . "\n";
