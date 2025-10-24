<?php
/**
 * Test script to check posts and generate thumbnails
 */

require_once __DIR__ . '/config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AI/ImageGenerator.php';

$db = Database::getInstance();

echo "=== LightBlog Thumbnail Diagnostic ===\n\n";

// Check posts
$posts = $db->query('SELECT id, title, slug, featured_image, status FROM posts ORDER BY id DESC LIMIT 10');

echo "Posts in database:\n";
echo str_repeat('=', 80) . "\n";
foreach ($posts as $post) {
    $hasThumbnail = !empty($post->featured_image) ? 'YES ✓' : 'NO ✗';
    echo sprintf(
        "ID: %d | %-40s | Thumbnail: %s\n",
        $post->id,
        substr($post->title, 0, 40),
        $hasThumbnail
    );
}
echo str_repeat('=', 80) . "\n\n";

// Check API keys
echo "Available AI Providers:\n";
echo str_repeat('=', 80) . "\n";
echo "OpenAI: " . (!empty(OPENAI_API_KEY) ? "Configured ✓" : "Not configured ✗") . "\n";
echo "Gemini: " . (!empty(GEMINI_API_KEY) ? "Configured ✓" : "Not configured ✗") . "\n";
echo "Claude: " . (!empty(CLAUDE_API_KEY) ? "Configured ✓" : "Not configured ✗") . "\n";
echo str_repeat('=', 80) . "\n\n";

// Test thumbnail generation
if (isset($argv[1]) && $argv[1] === '--generate') {
    echo "=== Generating Thumbnails ===\n\n";

    $postsToGenerate = $db->query('SELECT id, title FROM posts WHERE (featured_image IS NULL OR featured_image = "") AND status = "published" LIMIT 5');

    if (empty($postsToGenerate)) {
        echo "No posts need thumbnails!\n";
        exit(0);
    }

    foreach ($postsToGenerate as $post) {
        echo "Generating thumbnail for: {$post->title}...\n";

        try {
            $imageGen = new ImageGenerator('auto');
            $result = $imageGen->generateThumbnail($post->title, [
                'size' => '1200x630',
                'quality' => 'standard',
                'style' => 'professional'
            ]);

            echo "  ✓ Generated with {$result['provider']} (Cost: $" . number_format($result['cost'], 4) . ")\n";
            echo "  ✓ URL: {$result['image_url']}\n";

            // Update post
            $db->update('posts', [
                'featured_image' => $result['image_url']
            ], 'id = :id', ['id' => $post->id]);

            echo "  ✓ Post updated!\n\n";

        } catch (Exception $e) {
            echo "  ✗ Error: {$e->getMessage()}\n\n";
        }
    }

    echo "Done!\n";
} else {
    echo "To generate thumbnails for posts, run:\n";
    echo "php test-thumbnail-generation.php --generate\n";
}
