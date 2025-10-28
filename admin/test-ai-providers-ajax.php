<?php
/**
 * AJAX Handler for AI Providers Testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['provider'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$provider = $data['provider'];
$startTime = microtime(true);

try {
    switch ($provider) {
        case 'gemini':
            $result = testGemini();
            break;

        case 'openai':
            $result = testOpenAI();
            break;

        case 'claude':
            $result = testClaude();
            break;

        default:
            throw new Exception('Unknown provider: ' . $provider);
    }

    $endTime = microtime(true);
    $responseTime = round($endTime - $startTime, 2);

    echo json_encode([
        'success' => true,
        'message' => ucfirst($provider) . ' API working perfectly!',
        'response' => $result['content'],
        'stats' => [
            'response_time' => $responseTime,
            'characters' => strlen($result['content']),
            'model' => $result['model'] ?? 'Unknown'
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}

/**
 * Test Google Gemini API
 */
function testGemini() {
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

    if (empty($apiKey)) {
        throw new Exception('Gemini API key not configured');
    }

    require_once SITE_PATH . '/core/AI/GeminiProvider.php';

    $gemini = new GeminiProvider($apiKey, 'gemini-1.5-flash');

    // Test prompt - generate 5 blog topics
    $prompt = 'Generate 5 unique blog topic ideas about "Coffee and Productivity" in JSON array format.

Requirements:
- Each topic should be engaging and SEO-friendly
- Mix different formats (how-to, listicles, guides)
- Keep topics specific and actionable

Output only the JSON array:
["Topic 1", "Topic 2", "Topic 3", "Topic 4", "Topic 5"]';

    $result = $gemini->generate($prompt, [
        'temperature' => 0.8,
        'max_tokens' => 1000
    ]);

    return $result;
}

/**
 * Test OpenAI API
 */
function testOpenAI() {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

    if (empty($apiKey)) {
        throw new Exception('OpenAI API key not configured');
    }

    require_once SITE_PATH . '/core/AI/OpenAIProvider.php';

    $openai = new OpenAIProvider($apiKey, 'gpt-4o-mini');

    // Test prompt
    $prompt = 'Generate 5 unique blog topic ideas about "Coffee and Productivity" in JSON array format.

Requirements:
- Each topic should be engaging and SEO-friendly
- Mix different formats (how-to, listicles, guides)
- Keep topics specific and actionable

Output only the JSON array:
["Topic 1", "Topic 2", "Topic 3", "Topic 4", "Topic 5"]';

    $result = $openai->generate($prompt, [
        'temperature' => 0.8,
        'max_tokens' => 1000
    ]);

    return $result;
}

/**
 * Test Claude (Anthropic) API
 */
function testClaude() {
    $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';

    if (empty($apiKey)) {
        throw new Exception('Claude API key not configured');
    }

    require_once SITE_PATH . '/core/AI/ClaudeProvider.php';

    $claude = new ClaudeProvider($apiKey, 'claude-3-5-haiku-20241022');

    // Test prompt
    $prompt = 'Generate 5 unique blog topic ideas about "Coffee and Productivity" in JSON array format.

Requirements:
- Each topic should be engaging and SEO-friendly
- Mix different formats (how-to, listicles, guides)
- Keep topics specific and actionable

Output only the JSON array:
["Topic 1", "Topic 2", "Topic 3", "Topic 4", "Topic 5"]';

    $result = $claude->generate($prompt, [
        'temperature' => 0.8,
        'max_tokens' => 1000
    ]);

    return $result;
}
