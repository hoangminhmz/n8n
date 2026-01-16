<?php
/**
 * AJAX Bulk Operations for Posts
 * Handles: Auto-generate thumbnails, Auto-generate SEO data
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';
require_once SITE_PATH . '/core/AI/ImageGenerator.php';

header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$operation = $data['operation'] ?? '';
$postIds = $data['post_ids'] ?? [];

if (empty($operation) || empty($postIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing operation or post IDs']);
    exit;
}

$results = [
    'success' => true,
    'total' => count($postIds),
    'processed' => 0,
    'failed' => 0,
    'details' => []
];

switch ($operation) {
    case 'generate_thumbnails':
        foreach ($postIds as $postId) {
            try {
                $post = $db->queryOne("SELECT id, title, featured_image FROM posts WHERE id = ?", [(int)$postId]);

                if (!$post) {
                    $results['details'][] = ['id' => $postId, 'status' => 'error', 'message' => 'Post not found'];
                    $results['failed']++;
                    continue;
                }

                // Skip if already has thumbnail
                if (!empty($post->featured_image)) {
                    $results['details'][] = ['id' => $postId, 'status' => 'skipped', 'message' => 'Already has thumbnail'];
                    continue;
                }

                // Generate thumbnail
                $imageGen = new ImageGenerator('auto');
                $result = $imageGen->generateThumbnail($post->title, [
                    'size' => '1200x630',
                    'quality' => 'standard',
                    'style' => 'professional'
                ]);

                // Update post
                $db->update('posts', [
                    'featured_image' => $result['image_url']
                ], 'id = :id', ['id' => $postId]);

                $results['details'][] = [
                    'id' => $postId,
                    'status' => 'success',
                    'message' => "Generated with {$result['provider']}",
                    'cost' => $result['cost']
                ];
                $results['processed']++;

            } catch (Exception $e) {
                $results['details'][] = ['id' => $postId, 'status' => 'error', 'message' => $e->getMessage()];
                $results['failed']++;
            }
        }
        break;

    case 'generate_seo':
        foreach ($postIds as $postId) {
            try {
                $post = $db->queryOne("SELECT * FROM posts WHERE id = ?", [(int)$postId]);

                if (!$post) {
                    $results['details'][] = ['id' => $postId, 'status' => 'error', 'message' => 'Post not found'];
                    $results['failed']++;
                    continue;
                }

                // Skip if SEO already populated
                if (!empty($post->meta_description) && !empty($post->focus_keyword)) {
                    $results['details'][] = ['id' => $postId, 'status' => 'skipped', 'message' => 'SEO already filled'];
                    continue;
                }

                // Generate SEO data using AI
                $seoData = generateSEOData($post);

                // Update post
                $db->update('posts', $seoData, 'id = :id', ['id' => $postId]);

                $results['details'][] = [
                    'id' => $postId,
                    'status' => 'success',
                    'message' => 'SEO data generated'
                ];
                $results['processed']++;

            } catch (Exception $e) {
                $results['details'][] = ['id' => $postId, 'status' => 'error', 'message' => $e->getMessage()];
                $results['failed']++;
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown operation']);
        exit;
}

echo json_encode($results);

/**
 * Generate SEO data for a post using AI
 * @param object $post Post object
 * @return array SEO data to update
 */
function generateSEOData($post) {
    global $db;

    // Get AI provider setting
    $setting = $db->queryOne("SELECT value FROM settings WHERE `key` = 'content_ai_provider'");
    $provider = $setting->value ?? 'auto';

    // Initialize AI provider
    $aiProvider = null;
    if ($provider === 'auto' || $provider === 'gemini') {
        if (!empty(GEMINI_API_KEY)) {
            require_once SITE_PATH . '/core/AI/GeminiProvider.php';
            $aiProvider = new GeminiProvider(GEMINI_API_KEY, 'gemini-2.0-flash-exp');
        }
    }

    if (!$aiProvider && ($provider === 'auto' || $provider === 'claude')) {
        if (!empty(CLAUDE_API_KEY)) {
            require_once SITE_PATH . '/core/AI/ClaudeProvider.php';
            $aiProvider = new ClaudeProvider(CLAUDE_API_KEY);
        }
    }

    if (!$aiProvider && ($provider === 'auto' || $provider === 'openai')) {
        if (!empty(OPENAI_API_KEY)) {
            require_once SITE_PATH . '/core/AI/OpenAIProvider.php';
            $aiProvider = new OpenAIProvider(OPENAI_API_KEY, 'gpt-4-turbo');
        }
    }

    if (!$aiProvider) {
        throw new Exception('No AI provider available');
    }

    // Create prompt
    $prompt = "Generate SEO metadata for this blog post:\n\n";
    $prompt .= "Title: {$post->title}\n\n";
    $prompt .= "Content Preview: " . substr(strip_tags($post->content), 0, 500) . "...\n\n";
    $prompt .= "Generate the following in JSON format:\n";
    $prompt .= "1. focus_keyword: Main SEO keyword (1-3 words)\n";
    $prompt .= "2. meta_description: Meta description (150-160 characters)\n";
    $prompt .= "3. og_title: Open Graph title (compelling, 60 chars max)\n";
    $prompt .= "4. og_description: OG description (compelling, 150-200 chars)\n\n";
    $prompt .= "Return ONLY valid JSON, no other text.";

    $result = $aiProvider->generate($prompt, [
        'temperature' => 0.7,
        'max_tokens' => 500
    ]);

    // Parse JSON response
    $json = json_decode($result['content'], true);

    if (!$json) {
        // Fallback: extract from content
        return [
            'focus_keyword' => extractKeyword($post->title),
            'meta_description' => substr(strip_tags($post->excerpt ?: $post->content), 0, 160),
            'og_title' => $post->title,
            'og_description' => substr(strip_tags($post->excerpt ?: $post->content), 0, 200)
        ];
    }

    return [
        'focus_keyword' => $json['focus_keyword'] ?? '',
        'meta_description' => $json['meta_description'] ?? '',
        'og_title' => $json['og_title'] ?? '',
        'og_description' => $json['og_description'] ?? '',
        'twitter_title' => $json['og_title'] ?? '',
        'twitter_description' => $json['og_description'] ?? ''
    ];
}

/**
 * Extract main keyword from title (fallback)
 * @param string $title
 * @return string
 */
function extractKeyword($title) {
    $words = explode(' ', strtolower($title));
    $stopWords = ['how', 'to', 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'for', 'with'];
    $keywords = array_diff($words, $stopWords);
    return implode(' ', array_slice($keywords, 0, 3));
}
