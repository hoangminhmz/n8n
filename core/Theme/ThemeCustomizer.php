<?php
/**
 * LightBlog CMS - AI Theme Customizer
 * Generates and manages custom themes using AI
 */

class ThemeCustomizer {
    private $db;
    private $aiProvider;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Generate theme from AI prompt
     * @param string $userPrompt User's theme description
     * @param string $provider AI provider to use (gemini, openai, claude)
     * @param int $userId User ID for tracking
     * @return array Result with theme data or error
     */
    public function generateFromPrompt($userPrompt, $provider = 'gemini', $userId = null) {
        try {
            // Initialize AI provider
            $this->initializeAIProvider($provider);

            // Build comprehensive prompt
            $systemPrompt = $this->buildSystemPrompt();
            $fullPrompt = $systemPrompt . "\n\nUser Request: " . $userPrompt;

            // Call AI
            $result = $this->aiProvider->generate($fullPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 2000
            ]);

            // Parse response
            $themeData = $this->parseAIResponse($result['content']);

            // Validate theme data
            $validated = $this->validateThemeData($themeData);

            if (!$validated['valid']) {
                throw new Exception('Invalid theme data: ' . implode(', ', $validated['errors']));
            }

            // Generate CSS
            $css = $this->generateCSS($themeData);

            // Save to database (preview mode)
            $themeId = $this->saveTheme([
                'name' => $this->generateThemeName($userPrompt),
                'description' => $userPrompt,
                'css_variables' => json_encode($themeData['colors'] ?? []),
                'custom_css' => $css,
                'layout_style' => $themeData['layout']['style'] ?? 'card',
                'layout_spacing' => $themeData['layout']['spacing'] ?? 'normal',
                'border_radius' => $themeData['layout']['border_radius'] ?? '8px',
                'font_family' => $themeData['typography']['font_family'] ?? 'system-ui',
                'font_scale' => $themeData['typography']['font_scale'] ?? 1.00,
                'is_preview' => 1,
                'created_by' => $userId,
                'ai_prompt' => $userPrompt
            ]);

            return [
                'success' => true,
                'theme_id' => $themeId,
                'theme_data' => $themeData,
                'css' => $css,
                'preview_url' => $this->getPreviewUrl($themeId)
            ];

        } catch (Exception $e) {
            error_log('Theme generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build AI system prompt
     */
    private function buildSystemPrompt() {
        return 'You are a professional web designer specializing in blog themes. Generate theme customization settings.

Base theme: Simple, clean blog design with card/list layouts
Current style: Modern, minimalist, content-focused

IMPORTANT: Respond with ONLY a valid JSON object, no explanations, no markdown, no extra text.

Generate JSON with this exact structure:
{
  "colors": {
    "primary": "#667eea",
    "secondary": "#764ba2",
    "text": "#1f2937",
    "background": "#ffffff",
    "accent": "#f59e0b",
    "border": "#e5e7eb",
    "card_bg": "#ffffff",
    "hover": "#f3f4f6"
  },
  "typography": {
    "font_family": "system-ui, -apple-system, sans-serif",
    "font_scale": 1.0,
    "heading_weight": 600,
    "body_weight": 400
  },
  "layout": {
    "style": "card",
    "spacing": "normal",
    "border_radius": "8px",
    "container_width": "1200px"
  },
  "effects": {
    "card_shadow": "0 2px 4px rgba(0,0,0,0.1)",
    "hover_effect": "lift",
    "transition_speed": "0.3s"
  }
}

Requirements:
- WCAG AA contrast ratio (4.5:1 minimum for text)
- All colors must be valid hex codes (#RRGGBB)
- font_scale between 0.85 and 1.15
- border_radius: 0px, 4px, 8px, or 16px
- style: "card", "list", or "grid"
- spacing: "compact", "normal", or "spacious"

Design principles:
- Keep it simple and readable
- Prioritize content over decoration
- Ensure mobile-friendly choices
- Maintain professional appearance

Output ONLY the JSON, no explanations.';
    }

    /**
     * Parse AI response to extract JSON
     */
    private function parseAIResponse($content) {
        $originalContent = $content;
        $content = trim($content);

        // Strategy 1: Try to extract from markdown code blocks (```json or ```)
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }
        // Strategy 2: Try to extract from single backticks
        elseif (preg_match('/`(\{[\s\S]*?\})`/s', $content, $matches)) {
            $content = trim($matches[1]);
        }
        // Strategy 3: Try to find the first { to last } (complete JSON object)
        elseif (preg_match('/(\{(?:[^{}]|(?R))*\})/s', $content, $matches)) {
            $content = trim($matches[1]);
        }
        // Strategy 4: Remove any text before first { and after last }
        else {
            $firstBrace = strpos($content, '{');
            $lastBrace = strrpos($content, '}');

            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $content = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
            }
        }

        // Clean up common issues
        $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content); // Remove control characters
        $content = trim($content);

        // Try to decode
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the error with details
            error_log('JSON Parse Error: ' . json_last_error_msg());
            error_log('Original AI Response: ' . substr($originalContent, 0, 500));
            error_log('Extracted content: ' . substr($content, 0, 500));

            throw new Exception('Failed to parse AI response as JSON: ' . json_last_error_msg() . '. AI returned: ' . substr($originalContent, 0, 200) . '...');
        }

        return $data;
    }

    /**
     * Validate theme data
     */
    private function validateThemeData($data) {
        $errors = [];

        // Validate colors
        if (!isset($data['colors']) || !is_array($data['colors'])) {
            $errors[] = 'Missing colors object';
        } else {
            $requiredColors = ['primary', 'secondary', 'text', 'background'];
            foreach ($requiredColors as $color) {
                if (!isset($data['colors'][$color])) {
                    $errors[] = "Missing required color: $color";
                } elseif (!$this->isValidHexColor($data['colors'][$color])) {
                    $errors[] = "Invalid hex color for $color: {$data['colors'][$color]}";
                }
            }

            // Check contrast ratios
            if (isset($data['colors']['text']) && isset($data['colors']['background'])) {
                $contrast = $this->calculateContrast($data['colors']['text'], $data['colors']['background']);
                if ($contrast < 4.5) {
                    $errors[] = "Text/background contrast too low: {$contrast}:1 (minimum 4.5:1)";
                }
            }
        }

        // Validate layout
        if (isset($data['layout']['style'])) {
            if (!in_array($data['layout']['style'], ['card', 'list', 'grid'])) {
                $errors[] = "Invalid layout style: {$data['layout']['style']}";
            }
        }

        // Validate font_scale
        if (isset($data['typography']['font_scale'])) {
            $scale = (float)$data['typography']['font_scale'];
            if ($scale < 0.85 || $scale > 1.15) {
                $errors[] = "font_scale out of range: $scale (must be 0.85-1.15)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if string is valid hex color
     */
    private function isValidHexColor($color) {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color);
    }

    /**
     * Calculate WCAG contrast ratio
     */
    private function calculateContrast($color1, $color2) {
        $l1 = $this->getLuminance($color1);
        $l2 = $this->getLuminance($color2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * Get relative luminance of hex color
     */
    private function getLuminance($hex) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Generate CSS from theme data
     */
    private function generateCSS($data) {
        $css = "/* AI-Generated Theme Customization */\n\n";

        // CSS Variables
        $css .= ":root {\n";
        if (isset($data['colors'])) {
            foreach ($data['colors'] as $key => $value) {
                $cssVar = str_replace('_', '-', $key);
                $css .= "  --color-{$cssVar}: {$value};\n";
            }
        }

        if (isset($data['typography'])) {
            if (isset($data['typography']['font_family'])) {
                $css .= "  --font-family: {$data['typography']['font_family']};\n";
            }
            if (isset($data['typography']['font_scale'])) {
                $css .= "  --font-scale: {$data['typography']['font_scale']};\n";
            }
        }

        if (isset($data['layout']['border_radius'])) {
            $css .= "  --border-radius: {$data['layout']['border_radius']};\n";
        }

        $css .= "}\n\n";

        // Apply colors
        $css .= "body {\n";
        $css .= "  background-color: var(--color-background);\n";
        $css .= "  color: var(--color-text);\n";
        if (isset($data['typography']['font_family'])) {
            $css .= "  font-family: var(--font-family);\n";
        }
        $css .= "}\n\n";

        // Layout-specific styles
        $layoutStyle = $data['layout']['style'] ?? 'card';
        $css .= $this->generateLayoutCSS($layoutStyle, $data);

        return $css;
    }

    /**
     * Generate layout-specific CSS
     */
    private function generateLayoutCSS($style, $data) {
        $css = "/* Layout Style: {$style} */\n\n";

        if ($style === 'card') {
            $css .= ".post-card {\n";
            $css .= "  background: var(--color-card-bg, #fff);\n";
            $css .= "  border-radius: var(--border-radius);\n";
            $css .= "  box-shadow: " . ($data['effects']['card_shadow'] ?? '0 2px 4px rgba(0,0,0,0.1)') . ";\n";
            $css .= "  transition: transform " . ($data['effects']['transition_speed'] ?? '0.3s') . ";\n";
            $css .= "}\n\n";

            $css .= ".post-card:hover {\n";
            $css .= "  transform: translateY(-4px);\n";
            $css .= "  box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n";
            $css .= "}\n\n";
        } elseif ($style === 'list') {
            $css .= ".post-list-item {\n";
            $css .= "  border-bottom: 1px solid var(--color-border);\n";
            $css .= "  padding: 1.5rem 0;\n";
            $css .= "}\n\n";
        }

        return $css;
    }

    /**
     * Save theme to database
     */
    private function saveTheme($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert('theme_customizations', $data);
    }

    /**
     * Generate theme name from prompt
     */
    private function generateThemeName($prompt) {
        $name = substr($prompt, 0, 50);
        $name = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        return trim($name) ?: 'Custom Theme';
    }

    /**
     * Get preview URL for theme
     */
    private function getPreviewUrl($themeId) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '/';
        return $basePath . '?preview_theme=' . $themeId;
    }

    /**
     * Initialize AI provider
     */
    private function initializeAIProvider($provider) {
        switch ($provider) {
            case 'gemini':
                require_once __DIR__ . '/../AI/GeminiProvider.php';
                $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
                $this->aiProvider = new GeminiProvider($apiKey, 'gemini-2.5-flash');
                break;

            case 'openai':
                require_once __DIR__ . '/../AI/OpenAIProvider.php';
                $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
                $this->aiProvider = new OpenAIProvider($apiKey, 'gpt-4o-mini');
                break;

            case 'claude':
                require_once __DIR__ . '/../AI/ClaudeProvider.php';
                $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';
                $this->aiProvider = new ClaudeProvider($apiKey, 'claude-3-5-haiku-20241022');
                break;

            default:
                throw new Exception('Unsupported AI provider: ' . $provider);
        }
    }

    /**
     * Activate theme
     */
    public function activateTheme($themeId) {
        // Deactivate all other themes
        $this->db->query("UPDATE theme_customizations SET is_active = 0");

        // Activate this theme
        $this->db->query(
            "UPDATE theme_customizations SET is_active = 1, is_preview = 0, updated_at = NOW() WHERE id = ?",
            [$themeId]
        );

        return true;
    }

    /**
     * Get active theme
     */
    public function getActiveTheme() {
        return $this->db->queryOne("SELECT * FROM theme_customizations WHERE is_active = 1");
    }

    /**
     * Get all themes
     */
    public function getAllThemes() {
        return $this->db->query("SELECT * FROM theme_customizations ORDER BY created_at DESC");
    }

    /**
     * Delete theme
     */
    public function deleteTheme($themeId) {
        // Don't allow deleting active theme
        $theme = $this->db->queryOne("SELECT is_active FROM theme_customizations WHERE id = ?", [$themeId]);
        if ($theme && $theme->is_active) {
            throw new Exception('Cannot delete active theme');
        }

        $this->db->query("DELETE FROM theme_customizations WHERE id = ?", [$themeId]);
        return true;
    }
}
