<?php
/**
 * AJAX Endpoint - Auto-fill SEO fields with AI
 */

// Prevent any output before JSON
ob_start();

try {
    require_once __DIR__ . '/../config.php';
    require_once SITE_PATH . '/core/Database.php';
    require_once SITE_PATH . '/core/Auth.php';
    require_once SITE_PATH . '/core/SEO/SEOAnalyzer.php';
    require_once SITE_PATH . '/core/SEO/TOCGenerator.php';
    require_once SITE_PATH . '/core/SEO/FAQExtractor.php';

    // Clear any output from includes
    ob_clean();

    header('Content-Type: application/json');

    // Note: No authentication check needed here because:
    // 1. This file is in /admin/ directory
    // 2. User must be logged in to access /admin/posts.php
    // 3. AJAX requests inherit the session from the parent page
    // If you want to add auth check, ensure session_start() is called in config.php

    // Get database instance
    $db = Database::getInstance();
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';
    $postUrl = $input['postUrl'] ?? '';

    if (empty($title) || empty($content)) {
        throw new Exception('Title and content are required');
    }

    // Create a temporary post object for analysis
    $tempPost = (object)[
        'title' => $title,
        'content' => $content,
        'slug' => $postUrl,
        'focus_keyword' => '',
        'meta_description' => '',
        'seo_title' => $title
    ];

    // Extract focus keyword from title (use AI or simple extraction)
    $focusKeyword = extractFocusKeyword($title);

    // Generate SEO metadata using AI
    $seoData = generateSEOMetadata($title, $content, $focusKeyword);

    // Analyze content
    $seoAnalyzer = new SEOAnalyzer();
    $tempPost->focus_keyword = $focusKeyword;
    $tempPost->meta_description = $seoData['meta_description'];
    $seoMetrics = $seoAnalyzer->analyze($tempPost, $content);

    // Extract FAQ if available
    $faqExtractor = new FAQExtractor();
    $faqs = $faqExtractor->extract($content);
    $schemaType = $faqExtractor->detectSchemaType($content);
    $faqData = '';
    if (!empty($faqs)) {
        $faqSchema = $faqExtractor->generateSchema($faqs);
        $faqData = json_encode($faqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // Build canonical URL (SITE_URL already includes /lite, don't duplicate)
    $canonicalUrl = $postUrl ?: (SITE_URL . '/post/' . sanitizeSlug($title));

    // Return all SEO fields
    echo json_encode([
        'success' => true,
        'data' => [
            // SEO Meta
            'focus_keyword' => $focusKeyword,
            'seo_title' => $seoData['seo_title'],
            'meta_description' => $seoData['meta_description'],
            'canonical_url' => $canonicalUrl,
            'meta_robots' => 'index,follow',

            // Open Graph
            'og_title' => $seoData['og_title'],
            'og_description' => $seoData['og_description'],
            'og_image' => $seoData['og_image'],

            // Twitter Cards
            'twitter_title' => $seoData['twitter_title'],
            'twitter_description' => $seoData['twitter_description'],
            'twitter_image' => $seoData['twitter_image'],

            // Schema
            'schema_type' => $schemaType,
            'faq_data' => $faqData,

            // Metrics (read-only, but shown for preview)
            'word_count' => $seoMetrics['word_count'],
            'reading_time' => $seoMetrics['reading_time'],
            'readability_score' => round($seoMetrics['readability_score'], 1),
            'seo_score' => $seoMetrics['seo_score']
        ]
    ]);

} catch (Exception $e) {
    // Clear any output
    ob_clean();

    // Log the error
    error_log("AJAX Auto-fill SEO Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => SITE_DEBUG ? $e->getTraceAsString() : null
    ]);
} catch (Error $e) {
    // Catch PHP errors too
    ob_clean();

    error_log("AJAX Auto-fill SEO Fatal Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'trace' => SITE_DEBUG ? $e->getTraceAsString() : null
    ]);
}

// End output buffering
ob_end_flush();

/**
 * Extract focus keyword from title
 */
function extractFocusKeyword($title) {
    // Remove common words
    $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'how', 'what', 'when', 'where', 'why', 'who'];

    // Remove punctuation and split into words
    $cleanTitle = preg_replace('/[^\w\s]/', '', $title);
    $words = preg_split('/\s+/', strtolower($cleanTitle));
    $keywords = array_filter($words, function($word) use ($commonWords) {
        return strlen($word) > 3 && !in_array($word, $commonWords);
    });

    // Return first 2 meaningful words
    return implode(' ', array_slice($keywords, 0, 2));
}

/**
 * Generate SEO metadata using AI
 */
function generateSEOMetadata($title, $content, $focusKeyword) {
    // Use AI to generate optimized SEO metadata
    $db = Database::getInstance();

    // Get a campaign with AI settings (use first active campaign as default)
    $campaign = $db->queryOne("SELECT * FROM campaigns WHERE status = 'active' LIMIT 1");

    if (!$campaign) {
        // Fallback to manual generation
        return generateSEOMetadataManual($title, $content, $focusKeyword);
    }

    try {
        // Initialize AI provider
        $provider = null;
        $model = $campaign->ai_model;

        switch ($campaign->ai_provider) {
            case 'openai':
                require_once SITE_PATH . '/core/AI/Providers/OpenAIProvider.php';
                $provider = new OpenAIProvider($campaign->ai_api_key, $model);
                break;
            case 'claude':
                require_once SITE_PATH . '/core/AI/Providers/ClaudeProvider.php';
                $provider = new ClaudeProvider($campaign->ai_api_key, $model);
                break;
            case 'gemini':
                require_once SITE_PATH . '/core/AI/Providers/GeminiProvider.php';
                $provider = new GeminiProvider($campaign->ai_api_key, $model);
                break;
            default:
                throw new Exception('Unknown AI provider: ' . $campaign->ai_provider);
        }

        // Create prompt for SEO metadata generation
        $contentPreview = substr(strip_tags($content), 0, 500);

        $prompt = "Generate SEO metadata for this blog post. Return ONLY a JSON object with these fields:
- seo_title (50-60 chars, include focus keyword at start)
- meta_description (150-160 chars, compelling, include focus keyword)
- og_title (engaging social media title, can be different from SEO title)
- og_description (compelling description for social sharing)
- twitter_title (optimized for Twitter)
- twitter_description (optimized for Twitter)

Title: {$title}
Focus Keyword: {$focusKeyword}
Content Preview: {$contentPreview}

Return ONLY valid JSON, no markdown, no explanation:";

        $response = $provider->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);

        // Clean response (remove markdown code blocks if present)
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        $response = trim($response);

        $seoData = json_decode($response, true);

        if (!$seoData || !isset($seoData['seo_title'])) {
            throw new Exception('Invalid AI response');
        }

        // Add og_image and twitter_image (use featured image if available)
        $seoData['og_image'] = '';
        $seoData['twitter_image'] = '';

        return $seoData;

    } catch (Exception $e) {
        error_log("AI SEO generation failed: " . $e->getMessage());
        return generateSEOMetadataManual($title, $content, $focusKeyword);
    }
}

/**
 * Generate SEO metadata manually (fallback)
 */
function generateSEOMetadataManual($title, $content, $focusKeyword) {
    // Extract first paragraph for meta description
    $contentText = strip_tags($content);
    $sentences = preg_split('/[.!?]+/', $contentText);
    $metaDesc = '';

    foreach ($sentences as $sentence) {
        $sentence = trim($sentence);
        if (strlen($metaDesc . $sentence) < 155 && !empty($sentence)) {
            $metaDesc .= ($metaDesc ? '. ' : '') . $sentence;
        } else {
            break;
        }
    }

    if (empty($metaDesc)) {
        $metaDesc = substr($contentText, 0, 155);
    }

    // Ensure focus keyword is in meta description
    if (!empty($focusKeyword) && stripos($metaDesc, $focusKeyword) === false) {
        $metaDesc = $focusKeyword . ': ' . $metaDesc;
    }

    $metaDesc = substr($metaDesc, 0, 160);

    return [
        'seo_title' => $focusKeyword ? ($focusKeyword . ' - ' . $title) : $title,
        'meta_description' => $metaDesc,
        'og_title' => $title,
        'og_description' => $metaDesc,
        'og_image' => '',
        'twitter_title' => $title,
        'twitter_description' => substr($metaDesc, 0, 200),
        'twitter_image' => ''
    ];
}

/**
 * Sanitize string to URL-friendly slug
 */
function sanitizeSlug($str) {
    $slug = strtolower($str);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}
