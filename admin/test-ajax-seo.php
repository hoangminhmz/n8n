<?php
/**
 * Test AJAX Auto-fill SEO endpoint
 */

// Test directly to see actual errors
require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';
require_once SITE_PATH . '/core/SEO/SEOAnalyzer.php';
require_once SITE_PATH . '/core/SEO/TOCGenerator.php';
require_once SITE_PATH . '/core/SEO/FAQExtractor.php';

echo "<h1>Testing Auto-fill SEO Dependencies</h1>";

// Test 1: Database
echo "<h2>1. Database Connection</h2>";
try {
    $db = Database::getInstance();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: SEO Classes
echo "<h2>2. SEO Classes</h2>";
try {
    $analyzer = new SEOAnalyzer();
    echo "✅ SEOAnalyzer loaded<br>";
} catch (Exception $e) {
    echo "❌ SEOAnalyzer error: " . $e->getMessage() . "<br>";
}

try {
    $tocGen = new TOCGenerator();
    echo "✅ TOCGenerator loaded<br>";
} catch (Exception $e) {
    echo "❌ TOCGenerator error: " . $e->getMessage() . "<br>";
}

try {
    $faqExtractor = new FAQExtractor();
    echo "✅ FAQExtractor loaded<br>";
} catch (Exception $e) {
    echo "❌ FAQExtractor error: " . $e->getMessage() . "<br>";
}

// Test 3: Generate SEO metadata
echo "<h2>3. Test SEO Generation</h2>";

$testTitle = "The Future of Rest: What Advanced Materials and IoT Bring to the Best Mattress Designs";
$testContent = "<p>This is a test article about mattresses. It talks about advanced materials like memory foam and gel-infused layers.</p><p>IoT technology is revolutionizing how we sleep, with smart mattresses that track sleep patterns.</p>";

try {
    // Extract focus keyword
    $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'how', 'what', 'when', 'where', 'why', 'who'];
    $words = preg_split('/\s+/', strtolower($testTitle));
    $keywords = array_filter($words, function($word) use ($commonWords) {
        return strlen($word) > 3 && !in_array($word, $commonWords);
    });
    $focusKeyword = implode(' ', array_slice($keywords, 0, 2));

    echo "Focus Keyword: <strong>$focusKeyword</strong><br>";

    // Test SEO analysis
    $tempPost = (object)[
        'title' => $testTitle,
        'content' => $testContent,
        'slug' => 'test-slug',
        'focus_keyword' => $focusKeyword,
        'meta_description' => '',
        'seo_title' => $testTitle
    ];

    $seoAnalyzer = new SEOAnalyzer();
    $seoMetrics = $seoAnalyzer->analyze($tempPost, $testContent);

    echo "✅ SEO Analysis completed:<br>";
    echo "- Word count: {$seoMetrics['word_count']}<br>";
    echo "- Reading time: {$seoMetrics['reading_time']} min<br>";
    echo "- Readability: " . round($seoMetrics['readability_score'], 1) . "<br>";
    echo "- SEO Score: {$seoMetrics['seo_score']}/100<br>";

    // Test FAQ extraction
    $faqExtractor = new FAQExtractor();
    $faqs = $faqExtractor->extract($testContent);
    $schemaType = $faqExtractor->detectSchemaType($testContent);

    echo "✅ FAQ Extraction completed:<br>";
    echo "- Schema Type: $schemaType<br>";
    echo "- FAQs found: " . count($faqs) . "<br>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: AI Campaign check
echo "<h2>4. AI Campaign Check</h2>";
try {
    $campaign = $db->queryOne("SELECT * FROM campaigns WHERE status = 'active' LIMIT 1");
    if ($campaign) {
        echo "✅ Found active campaign: {$campaign->name}<br>";
        echo "- AI Provider: {$campaign->ai_provider}<br>";
        echo "- AI Model: {$campaign->ai_model}<br>";
    } else {
        echo "⚠️ No active campaign found. Will use fallback manual generation.<br>";
    }
} catch (Exception $e) {
    echo "❌ Campaign query error: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "If all tests passed, the Auto-fill SEO button should work. If you see errors above, those need to be fixed first.";
