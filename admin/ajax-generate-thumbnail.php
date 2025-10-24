<?php
/**
 * AJAX endpoint to generate thumbnail image for posts
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Auth.php';

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

try {
    // Generate thumbnail using PHP GD
    $width = 1200;
    $height = 630; // Standard OG image size

    // Create image
    $image = imagecreatetruecolor($width, $height);

    // Random gradient colors (professional color schemes)
    $colorSchemes = [
        ['start' => [59, 130, 246], 'end' => [147, 51, 234]],    // Blue to Purple
        ['start' => [16, 185, 129], 'end' => [6, 182, 212]],     // Green to Cyan
        ['start' => [245, 158, 11], 'end' => [239, 68, 68]],     // Orange to Red
        ['start' => [139, 92, 246], 'end' => [236, 72, 153]],    // Purple to Pink
        ['start' => [34, 197, 94], 'end' => [234, 179, 8]],      // Green to Yellow
    ];

    $scheme = $colorSchemes[array_rand($colorSchemes)];

    // Create gradient background
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = $scheme['start'][0] + ($scheme['end'][0] - $scheme['start'][0]) * $ratio;
        $g = $scheme['start'][1] + ($scheme['end'][1] - $scheme['start'][1]) * $ratio;
        $b = $scheme['start'][2] + ($scheme['end'][2] - $scheme['start'][2]) * $ratio;

        $color = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, $i, $width, $i + 1, $color);
    }

    // Add semi-transparent overlay for better text readability
    $overlay = imagecolorallocatealpha($image, 0, 0, 0, 40);
    imagefilledrectangle($image, 0, 0, $width, $height, $overlay);

    // Text color (white)
    $textColor = imagecolorallocate($image, 255, 255, 255);

    // Try to use a nice font if available
    $fontPath = null;
    $possibleFonts = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/System/Library/Fonts/Helvetica.ttc',
        'C:\Windows\Fonts\arial.ttf',
    ];

    foreach ($possibleFonts as $font) {
        if (file_exists($font)) {
            $fontPath = $font;
            break;
        }
    }

    // Wrap text
    $maxWidth = $width - 200; // 100px padding on each side
    $fontSize = 60;
    $lines = [];

    if ($fontPath) {
        // Use TrueType font
        $words = explode(' ', $title);
        $line = '';

        foreach ($words as $word) {
            $testLine = $line . ($line ? ' ' : '') . $word;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $testLine);
            $lineWidth = $bbox[2] - $bbox[0];

            if ($lineWidth > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $testLine;
            }
        }
        if ($line) {
            $lines[] = $line;
        }

        // Limit to 3 lines
        $lines = array_slice($lines, 0, 3);
        if (count($lines) === 3 && count(explode(' ', $title)) > count(explode(' ', implode(' ', $lines)))) {
            $lines[2] .= '...';
        }

        // Draw text centered
        $lineHeight = 80;
        $totalHeight = count($lines) * $lineHeight;
        $startY = ($height - $totalHeight) / 2 + 60;

        foreach ($lines as $i => $line) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $line);
            $textWidth = $bbox[2] - $bbox[0];
            $x = ($width - $textWidth) / 2;
            $y = $startY + ($i * $lineHeight);

            // Add text shadow for better readability
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 50);
            imagettftext($image, $fontSize, 0, $x + 3, $y + 3, $shadowColor, $fontPath, $line);
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $line);
        }
    } else {
        // Fallback to built-in font
        $line = wordwrap($title, 40, "\n");
        $lines = explode("\n", $line);
        $lines = array_slice($lines, 0, 3);

        $font = 5; // Built-in large font
        $lineHeight = 20;
        $totalHeight = count($lines) * $lineHeight;
        $startY = ($height - $totalHeight) / 2;

        foreach ($lines as $i => $textLine) {
            $textWidth = imagefontwidth($font) * strlen($textLine);
            $x = ($width - $textWidth) / 2;
            $y = $startY + ($i * $lineHeight);
            imagestring($image, $font, $x, $y, $textLine, $textColor);
        }
    }

    // Save image
    $uploadsDir = CONTENT_PATH . '/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $filename = 'thumb-' . time() . '-' . substr(md5($title), 0, 8) . '.jpg';
    $filepath = $uploadsDir . '/' . $filename;

    // Save as JPEG with high quality
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);

    // Generate URL
    $imageUrl = SITE_URL . '/content/uploads/' . $filename;

    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl,
        'filename' => $filename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate thumbnail: ' . $e->getMessage()
    ]);
}
