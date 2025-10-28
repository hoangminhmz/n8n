<?php
/**
 * LightBlog CMS - AJAX Prompt Test Handler
 * Tests prompt templates with sample data
 */

session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/AI/PromptManager.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['template_key']) || !isset($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$templateKey = $input['template_key'];
$prompt = $input['prompt'];

$promptManager = new PromptManager();

// Validate the prompt
$validation = $promptManager->validatePrompt($templateKey, $prompt);

// Get sample data for the template type
$sampleData = getSampleDataForTemplate($templateKey);

// Test the prompt with sample data
$preview = $promptManager->testPrompt($prompt, $sampleData);

// Return results
echo json_encode([
    'success' => true,
    'preview' => $preview,
    'validation' => $validation,
    'warnings' => $validation['warnings'],
    'errors' => $validation['errors'],
    'sample_data' => $sampleData
]);

/**
 * Get sample data for different template types
 */
function getSampleDataForTemplate($templateKey) {
    $sampleDataMap = [
        'post_title' => [
            'topic' => 'Best Coffee Makers for Home Use in 2025',
            'primary_keyword' => 'coffee maker',
            'niche' => 'Kitchen Appliances',
            'tone' => 'professional',
            'year' => date('Y')
        ],

        'post_outline' => [
            'topic' => 'Best Coffee Makers for Home Use in 2025',
            'primary_keywords' => 'coffee maker, drip coffee, espresso machine',
            'lsi_keywords' => 'brewing coffee, coffee quality, home barista',
            'word_count_min' => '1500',
            'word_count_max' => '2500',
            'niche' => 'Kitchen Appliances'
        ],

        'post_content' => [
            'outline' => json_encode([
                'h1' => 'Best Coffee Makers for Home Use in 2025',
                'sections' => [
                    ['h2' => 'Introduction', 'h3' => []],
                    ['h2' => 'Top 5 Coffee Makers', 'h3' => ['Drip Coffee Makers', 'Espresso Machines']],
                    ['h2' => 'Buying Guide', 'h3' => ['Key Features', 'Price Ranges']]
                ]
            ], JSON_PRETTY_PRINT),
            'tone' => 'professional and helpful',
            'primary_keywords' => 'coffee maker, brewing, espresso',
            'niche' => 'Kitchen Appliances',
            'word_count' => '2000'
        ],

        'post_meta_description' => [
            'title' => 'Best Coffee Makers for Home Use in 2025',
            'focus_keyword' => 'coffee maker',
            'niche' => 'Kitchen Appliances'
        ],

        'post_excerpt' => [
            'title' => 'Best Coffee Makers for Home Use in 2025',
            'first_paragraph' => 'Finding the perfect coffee maker can transform your morning routine. In this comprehensive guide, we review the top coffee makers available in 2025, covering drip coffee makers, espresso machines, and single-serve brewers.',
            'content' => 'Finding the perfect coffee maker can transform your morning routine...'
        ],

        'post_faq' => [
            'topic' => 'Best Coffee Makers for Home Use',
            'niche' => 'Kitchen Appliances',
            'target_audience' => 'home coffee enthusiasts',
            'content' => 'Article about coffee makers and brewing techniques...'
        ],

        'campaign_topics' => [
            'count' => '10',
            'niche' => 'Kitchen Appliances',
            'seed_keywords' => 'coffee maker, espresso, brewing',
            'target_audience' => 'home coffee enthusiasts',
            'existing_topics' => "- Best Drip Coffee Makers 2024\n- How to Choose an Espresso Machine\n- Coffee Grinder Buying Guide",
            'year' => date('Y')
        ],

        'campaign_keywords' => [
            'topic' => 'Best Coffee Makers for Home',
            'primary_keyword' => 'coffee maker',
            'niche' => 'Kitchen Appliances'
        ],

        'image_generation' => [
            'topic' => 'Modern coffee maker in stylish kitchen',
            'style' => 'professional, high-quality, clean composition',
            'niche' => 'Kitchen Appliances'
        ],

        'image_search' => [
            'topic' => 'Best Coffee Makers for Home Use in 2025',
            'niche' => 'Kitchen Appliances'
        ]
    ];

    return $sampleDataMap[$templateKey] ?? [
        'topic' => 'Sample Topic',
        'niche' => 'General'
    ];
}
