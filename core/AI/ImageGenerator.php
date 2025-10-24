<?php
/**
 * LightBlog CMS - Image Generator
 * Generates images for posts using AI providers or PHP GD fallback
 */

require_once __DIR__ . '/../Database.php';

class ImageGenerator {
    private $db;
    private $provider;
    private $preferredProvider;

    /**
     * Constructor
     * @param string|null $preferredProvider Preferred AI provider (openai, gemini, claude, or null for config default)
     */
    public function __construct($preferredProvider = null) {
        $this->db = Database::getInstance();

        // Get preferred provider from settings or use parameter
        if ($preferredProvider) {
            $this->preferredProvider = $preferredProvider;
        } else {
            // Get from database settings table
            $setting = $this->db->queryOne("SELECT value FROM settings WHERE key = 'image_ai_provider'");
            $this->preferredProvider = $setting->value ?? 'auto';
        }

        // Initialize provider
        $this->initializeProvider();
    }

    /**
     * Initialize AI provider based on preference and availability
     */
    private function initializeProvider() {
        // If 'auto', try OpenAI first (best image quality), then others
        if ($this->preferredProvider === 'auto') {
            if (!empty(OPENAI_API_KEY)) {
                $this->preferredProvider = 'openai';
            } elseif (!empty(GEMINI_API_KEY)) {
                $this->preferredProvider = 'gemini';
            } elseif (!empty(CLAUDE_API_KEY)) {
                $this->preferredProvider = 'claude';
            } else {
                $this->preferredProvider = 'php-gd'; // Fallback to PHP GD
            }
        }

        // Load provider
        try {
            switch ($this->preferredProvider) {
                case 'openai':
                    if (empty(OPENAI_API_KEY)) {
                        throw new Exception('OpenAI API key not configured');
                    }
                    require_once __DIR__ . '/OpenAIProvider.php';
                    $this->provider = new OpenAIProvider(OPENAI_API_KEY);
                    break;

                case 'gemini':
                    if (empty(GEMINI_API_KEY)) {
                        throw new Exception('Gemini API key not configured');
                    }
                    require_once __DIR__ . '/GeminiProvider.php';
                    $this->provider = new GeminiProvider(GEMINI_API_KEY);
                    break;

                case 'claude':
                    if (empty(CLAUDE_API_KEY)) {
                        throw new Exception('Claude API key not configured');
                    }
                    require_once __DIR__ . '/ClaudeProvider.php';
                    $this->provider = new ClaudeProvider(CLAUDE_API_KEY);
                    break;

                case 'php-gd':
                    $this->provider = null; // Will use PHP GD fallback
                    break;

                default:
                    $this->provider = null;
            }
        } catch (Exception $e) {
            error_log('ImageGenerator: Failed to initialize provider: ' . $e->getMessage());
            $this->provider = null; // Fallback to PHP GD
        }
    }

    /**
     * Generate image for post thumbnail
     * @param string $title Post title (used for prompt and fallback text)
     * @param array $options Options (size, quality, style, save_to_disk)
     * @return array ['image_url' => string, 'provider' => string, 'cost' => float]
     */
    public function generateThumbnail($title, $options = []) {
        $size = $options['size'] ?? '1200x630'; // Default OG image size
        $quality = $options['quality'] ?? 'standard';
        $style = $options['style'] ?? 'professional';

        // Try AI generation first
        if ($this->provider) {
            try {
                $prompt = $this->createImagePrompt($title, $style);

                $result = $this->provider->generateImage($prompt, [
                    'size' => $size === '1200x630' ? '1024x1024' : $size, // Map to provider size
                    'quality' => $quality,
                    'style' => $style === 'professional' ? 'natural' : 'vivid'
                ]);

                // Download and save the image locally
                if (!empty($result['image_url'])) {
                    $localUrl = $this->downloadAndSaveImage($result['image_url'], $title);
                    if ($localUrl) {
                        $result['image_url'] = $localUrl;
                        $result['provider'] = $this->preferredProvider;
                        return $result;
                    }
                }

                return $result;

            } catch (Exception $e) {
                error_log('ImageGenerator: AI generation failed: ' . $e->getMessage());
                // Fall through to PHP GD fallback
            }
        }

        // Fallback to PHP GD generation
        return $this->generateWithPHPGD($title, $size);
    }

    /**
     * Create optimized prompt for image generation
     * @param string $title Post title
     * @param string $style Style (professional, creative, minimal, vibrant)
     * @return string
     */
    private function createImagePrompt($title, $style = 'professional') {
        $styleDescriptions = [
            'professional' => 'professional, clean, modern, business-like',
            'creative' => 'creative, artistic, imaginative, colorful',
            'minimal' => 'minimalist, simple, elegant, clean lines',
            'vibrant' => 'vibrant, energetic, bold colors, dynamic'
        ];

        $styleDesc = $styleDescriptions[$style] ?? $styleDescriptions['professional'];

        // Create a clear, descriptive prompt
        $prompt = "Create a {$styleDesc} featured image for a blog post titled: \"{$title}\". ";
        $prompt .= "The image should be eye-catching and relevant to the topic. ";
        $prompt .= "Include text overlay with the title in large, readable font. ";
        $prompt .= "Use a gradient or solid color background. ";
        $prompt .= "Make it suitable for social media sharing (1200x630px).";

        return $prompt;
    }

    /**
     * Download AI-generated image and save locally
     * @param string $remoteUrl Remote image URL
     * @param string $title Post title for filename
     * @return string|null Local URL or null on failure
     */
    private function downloadAndSaveImage($remoteUrl, $title) {
        try {
            // Download image
            $imageData = file_get_contents($remoteUrl);
            if ($imageData === false) {
                return null;
            }

            // Create uploads directory if needed
            $uploadsDir = CONTENT_PATH . '/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Generate filename
            $filename = 'ai-thumb-' . time() . '-' . substr(md5($title), 0, 8) . '.jpg';
            $filepath = $uploadsDir . '/' . $filename;

            // Save image
            file_put_contents($filepath, $imageData);

            // Return URL
            return SITE_URL . '/content/uploads/' . $filename;

        } catch (Exception $e) {
            error_log('ImageGenerator: Failed to download image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate image using PHP GD (fallback)
     * @param string $title Post title
     * @param string $size Image size (1200x630, 1024x1024, etc.)
     * @return array
     */
    private function generateWithPHPGD($title, $size = '1200x630') {
        try {
            // Parse size
            list($width, $height) = explode('x', $size);
            $width = (int)$width;
            $height = (int)$height;

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
            $fontPath = $this->findSystemFont();
            $fontSize = 60;
            $lines = $this->wrapText($title, $width - 200, $fontSize, $fontPath);

            // Draw text centered
            $this->drawCenteredText($image, $lines, $width, $height, $fontSize, $textColor, $fontPath);

            // Save image
            $uploadsDir = CONTENT_PATH . '/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $filename = 'thumb-' . time() . '-' . substr(md5($title), 0, 8) . '.jpg';
            $filepath = $uploadsDir . '/' . $filename;

            imagejpeg($image, $filepath, 90);
            imagedestroy($image);

            $imageUrl = SITE_URL . '/content/uploads/' . $filename;

            return [
                'image_url' => $imageUrl,
                'provider' => 'php-gd',
                'cost' => 0.0,
                'filename' => $filename
            ];

        } catch (Exception $e) {
            throw new Exception('PHP GD generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Find system font for text rendering
     * @return string|null Font path or null
     */
    private function findSystemFont() {
        $possibleFonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            'C:\Windows\Fonts\arial.ttf',
        ];

        foreach ($possibleFonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Wrap text to fit within width
     * @param string $text Text to wrap
     * @param int $maxWidth Maximum width in pixels
     * @param int $fontSize Font size
     * @param string|null $fontPath Font path
     * @return array Lines of text
     */
    private function wrapText($text, $maxWidth, $fontSize, $fontPath = null) {
        if (!$fontPath) {
            // Fallback to built-in font wrapping
            return explode("\n", wordwrap($text, 40, "\n"));
        }

        $words = explode(' ', $text);
        $lines = [];
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
        return array_slice($lines, 0, 3);
    }

    /**
     * Draw centered text on image
     * @param resource $image GD image resource
     * @param array $lines Lines of text
     * @param int $width Image width
     * @param int $height Image height
     * @param int $fontSize Font size
     * @param int $textColor Text color
     * @param string|null $fontPath Font path
     */
    private function drawCenteredText($image, $lines, $width, $height, $fontSize, $textColor, $fontPath = null) {
        if (!$fontPath) {
            // Fallback to built-in font
            $font = 5;
            $lineHeight = 20;
            $totalHeight = count($lines) * $lineHeight;
            $startY = ($height - $totalHeight) / 2;

            foreach ($lines as $i => $textLine) {
                $textWidth = imagefontwidth($font) * strlen($textLine);
                $x = ($width - $textWidth) / 2;
                $y = $startY + ($i * $lineHeight);
                imagestring($image, $font, $x, $y, $textLine, $textColor);
            }
            return;
        }

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
    }
}
