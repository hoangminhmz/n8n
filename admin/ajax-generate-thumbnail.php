<?php
/**
 * AJAX endpoint to generate thumbnail image for posts
 * Uses AI providers (OpenAI DALL-E 3, Gemini, Claude) with PHP GD fallback
 */

require_once __DIR__ . '/../config.php';
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

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['title']) || empty(trim($data['title']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

$title = trim($data['title']);
$provider = $data['provider'] ?? 'auto'; // Can be: auto, openai, gemini, claude, php-gd
$style = $data['style'] ?? 'professional'; // professional, creative, minimal, vibrant

try {
    // Initialize Image Generator
    $imageGen = new ImageGenerator($provider);

    // Generate thumbnail
    $result = $imageGen->generateThumbnail($title, [
        'size' => '1200x630', // OG image standard
        'quality' => 'standard',
        'style' => $style
    ]);

    echo json_encode([
        'success' => true,
        'image_url' => $result['image_url'],
        'provider' => $result['provider'],
        'cost' => $result['cost'] ?? 0.0,
        'filename' => basename($result['image_url'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate thumbnail: ' . $e->getMessage()
    ]);
}
