<?php
/**
 * Debug version - Shows real PHP errors
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug Mode: Starting script -->\n";

try {
    echo "<!-- Step 1: Loading config -->\n";
    require_once __DIR__ . '/../config.php';
    echo "<!-- Config loaded -->\n";

    echo "<!-- Step 2: Loading Database -->\n";
    require_once SITE_PATH . '/core/Database.php';
    echo "<!-- Database class loaded -->\n";

    echo "<!-- Step 3: Loading Auth -->\n";
    require_once SITE_PATH . '/core/Auth.php';
    echo "<!-- Auth class loaded -->\n";

    echo "<!-- Step 4: Loading SEO classes -->\n";
    require_once SITE_PATH . '/core/SEO/SEOAnalyzer.php';
    require_once SITE_PATH . '/core/SEO/TOCGenerator.php';
    require_once SITE_PATH . '/core/SEO/FAQExtractor.php';
    echo "<!-- SEO classes loaded -->\n";

    echo "<!-- Step 5: Skipping session check (not needed in admin dir) -->\n";
    // No authentication check - user must be logged in to access /admin/ directory

    echo "<!-- Step 6: Getting database instance -->\n";
    $db = Database::getInstance();
    echo "<!-- Database instance OK -->\n";

    echo "<!-- Step 7: Reading POST data -->\n";
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('No POST data received. Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    }
    echo "<!-- POST data: " . json_encode($input) . " -->\n";

    $title = $input['title'] ?? '';
    $content = $input['content'] ?? '';
    $postUrl = $input['postUrl'] ?? '';

    if (empty($title) || empty($content)) {
        throw new Exception('Title and content are required');
    }
    echo "<!-- Title and content OK -->\n";

    echo "<!-- Step 8: Processing... -->\n";

    // Extract focus keyword
    function extractFocusKeyword($title) {
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'how', 'what', 'when', 'where', 'why', 'who'];
        $cleanTitle = preg_replace('/[^\w\s]/', '', $title);
        $words = preg_split('/\s+/', strtolower($cleanTitle));
        $keywords = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 3 && !in_array($word, $commonWords);
        });
        return implode(' ', array_slice($keywords, 0, 2));
    }

    $focusKeyword = extractFocusKeyword($title);
    echo "<!-- Focus keyword: $focusKeyword -->\n";

    // Manual SEO generation
    function generateSEOMetadataManual($title, $content, $focusKeyword) {
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

    $seoData = generateSEOMetadataManual($title, $content, $focusKeyword);
    echo "<!-- SEO data generated -->\n";

    // Analyze content
    $tempPost = (object)[
        'title' => $title,
        'content' => $content,
        'slug' => $postUrl,
        'focus_keyword' => $focusKeyword,
        'meta_description' => $seoData['meta_description'],
        'seo_title' => $title
    ];

    $seoAnalyzer = new SEOAnalyzer();
    $seoMetrics = $seoAnalyzer->analyze($tempPost, $content);
    echo "<!-- SEO analysis done -->\n";

    // Extract FAQ
    $faqExtractor = new FAQExtractor();
    $faqs = $faqExtractor->extract($content);
    $schemaType = $faqExtractor->detectSchemaType($content);
    $faqData = '';
    if (!empty($faqs)) {
        $faqSchema = $faqExtractor->generateSchema($faqs);
        $faqData = json_encode($faqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    echo "<!-- FAQ extraction done -->\n";

    // Build canonical URL
    function sanitizeSlug($str) {
        $slug = strtolower($str);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    $canonicalUrl = $postUrl ?: (SITE_URL . '/post/' . sanitizeSlug($title));
    echo "<!-- Canonical URL: $canonicalUrl -->\n";

    echo "<!-- Step 9: Building JSON response -->\n";

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'focus_keyword' => $focusKeyword,
            'seo_title' => $seoData['seo_title'],
            'meta_description' => $seoData['meta_description'],
            'canonical_url' => $canonicalUrl,
            'meta_robots' => 'index,follow',
            'og_title' => $seoData['og_title'],
            'og_description' => $seoData['og_description'],
            'og_image' => $seoData['og_image'],
            'twitter_title' => $seoData['twitter_title'],
            'twitter_description' => $seoData['twitter_description'],
            'twitter_image' => $seoData['twitter_image'],
            'schema_type' => $schemaType,
            'faq_data' => $faqData,
            'word_count' => $seoMetrics['word_count'],
            'reading_time' => $seoMetrics['reading_time'],
            'readability_score' => round($seoMetrics['readability_score'], 1),
            'seo_score' => $seoMetrics['seo_score']
        ],
        'debug' => 'All steps completed successfully'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
} catch (Error $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
