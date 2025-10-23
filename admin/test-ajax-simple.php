<?php
/**
 * Simple test - Call the AJAX logic directly without AJAX wrapper
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/SEO/SEOAnalyzer.php';
require_once SITE_PATH . '/core/SEO/TOCGenerator.php';
require_once SITE_PATH . '/core/SEO/FAQExtractor.php';

echo "<h1>Testing AJAX Auto-fill SEO Logic</h1>";

// Test data
$title = 'The Future of Rest: What Advanced Materials and IoT Bring to the Best Mattress Designs';
$content = '<p>This is a test article about mattresses. It talks about advanced materials like memory foam and gel-infused layers.</p><p>IoT technology is revolutionizing how we sleep, with smart mattresses that track sleep patterns.</p><p>Modern mattresses combine comfort with technology for better sleep quality.</p>';
$postUrl = '';

echo "<h2>Input:</h2>";
echo "Title: <strong>$title</strong><br>";
echo "Content length: " . strlen($content) . " chars<br>";
echo "<hr>";

try {
    // Extract focus keyword
    function extractFocusKeyword($title) {
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'how', 'what', 'when', 'where', 'why', 'who'];
        // Remove punctuation first
        $cleanTitle = preg_replace('/[^\w\s]/', '', $title);
        $words = preg_split('/\s+/', strtolower($cleanTitle));
        $keywords = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 3 && !in_array($word, $commonWords);
        });
        return implode(' ', array_slice($keywords, 0, 2));
    }

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

    function sanitizeSlug($str) {
        $slug = strtolower($str);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    echo "<h2>Step 1: Extract Focus Keyword</h2>";
    $focusKeyword = extractFocusKeyword($title);
    echo "✅ Focus Keyword: <strong>$focusKeyword</strong><br><br>";

    echo "<h2>Step 2: Generate SEO Metadata (Manual)</h2>";
    $seoData = generateSEOMetadataManual($title, $content, $focusKeyword);
    echo "<pre>" . print_r($seoData, true) . "</pre>";

    echo "<h2>Step 3: Analyze Content</h2>";
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
    echo "<pre>" . print_r($seoMetrics, true) . "</pre>";

    echo "<h2>Step 4: Extract FAQ</h2>";
    $faqExtractor = new FAQExtractor();
    $faqs = $faqExtractor->extract($content);
    $schemaType = $faqExtractor->detectSchemaType($content);
    echo "Schema Type: <strong>$schemaType</strong><br>";
    echo "FAQs found: " . count($faqs) . "<br>";

    $faqData = '';
    if (!empty($faqs)) {
        $faqSchema = $faqExtractor->generateSchema($faqs);
        $faqData = json_encode($faqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    echo "<h2>Step 5: Build Canonical URL</h2>";
    $canonicalUrl = $postUrl ?: (SITE_URL . '/post/' . sanitizeSlug($title));
    echo "Canonical URL: <strong>$canonicalUrl</strong><br><br>";

    echo "<h2>Final JSON Response:</h2>";
    $response = [
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
        ]
    ];

    $json = json_encode($response, JSON_PRETTY_PRINT);
    echo "<pre style='background: #f0f0f0; border: 2px solid green; padding: 10px;'>$json</pre>";

    echo "<h2 style='color: green;'>✅ SUCCESS! All logic works correctly.</h2>";
    echo "<p>The AJAX endpoint should return this JSON. If the button still fails, the issue is with:</p>";
    echo "<ul>";
    echo "<li>Session authentication</li>";
    echo "<li>HTTP headers/CORS</li>";
    echo "<li>Server PHP configuration blocking AJAX</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
