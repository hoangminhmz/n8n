<?php
/**
 * Theme Generation Debug Tool
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Debug Theme Generation';
$auth = new Auth();
$auth->requireLogin();

// Process test request
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = trim($_POST['prompt'] ?? 'Modern minimalist theme with blue accents');
    $provider = $_POST['provider'] ?? 'gemini';

    try {
        // Initialize AI provider
        require_once SITE_PATH . '/core/AI/AIProviderFactory.php';
        $aiProvider = AIProviderFactory::create($provider);

        // Build system prompt (same as ThemeCustomizer)
        $systemPrompt = 'You are a professional web designer specializing in blog themes. Generate theme customization settings.

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
    "shadow_strength": "medium"
  },
  "effects": {
    "transitions": true,
    "hover_effects": true,
    "animations": "subtle"
  }
}

Requirements:
- WCAG AA contrast ratio (4.5:1 minimum for text)
- All colors must be valid hex codes (#RRGGBB)
- font_scale between 0.85 and 1.15
- border_radius: 0px, 4px, 8px, or 16px
- style: "card", "list", or "grid"
- spacing: "compact", "normal", or "spacious"

Output ONLY the JSON, no explanations.';

        $fullPrompt = $systemPrompt . "\n\nUser request: " . $prompt;

        // Call AI
        $aiResult = $aiProvider->generate($fullPrompt, [
            'temperature' => 0.7,
            'max_tokens' => 2000
        ]);

        $rawResponse = $aiResult['content'];

        // Try to parse manually
        $parsed = null;
        $parseError = null;

        try {
            $content = trim($rawResponse);

            // Try different extraction methods
            if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/s', $content, $matches)) {
                $content = trim($matches[1]);
            } elseif (preg_match('/`(\{[\s\S]*?\})`/s', $content, $matches)) {
                $content = trim($matches[1]);
            } else {
                $firstBrace = strpos($content, '{');
                $lastBrace = strrpos($content, '}');

                if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                    $content = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
                }
            }

            // Clean and decode
            $content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $parseError = json_last_error_msg();
            }
        } catch (Exception $e) {
            $parseError = $e->getMessage();
        }

        $result = [
            'success' => ($parsed !== null && $parseError === null),
            'parsed' => $parsed,
            'raw' => $rawResponse,
            'error' => $parseError,
            'extracted_content' => $content ?? null
        ];

    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => $e->getMessage(),
            'raw' => null
        ];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.debug-container {
    max-width: 1200px;
    margin: 0 auto;
}

.debug-form {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 80px;
}

.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.debug-output {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.output-section {
    margin-bottom: 24px;
}

.output-section h3 {
    margin: 0 0 12px 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
}

.output-content {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 16px;
    font-family: 'Monaco', 'Courier New', monospace;
    font-size: 13px;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 400px;
    overflow-y: auto;
}

.success {
    color: #059669;
    font-weight: 600;
}

.error {
    color: #dc2626;
    font-weight: 600;
}

.btn-test {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-test:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 24px;
}

.info-box h4 {
    margin: 0 0 8px 0;
    color: #1e40af;
    font-size: 14px;
}

.info-box p {
    margin: 0;
    color: #1e3a8a;
    font-size: 13px;
}
</style>

<div class="debug-container">
    <h2>🔍 Theme Generation Debug Tool</h2>

    <div class="info-box">
        <h4>ℹ️ How to use this tool</h4>
        <p>This tool lets you test theme generation and see the raw AI response. Enter a theme description, choose an AI provider, and click "Test Generation". You'll see the complete AI response and whether it was successfully parsed as JSON.</p>
    </div>

    <form method="POST" class="debug-form">
        <div class="form-group">
            <label for="prompt">Theme Description:</label>
            <textarea name="prompt" id="prompt" placeholder="Modern minimalist theme with blue accents, card layout"><?= htmlspecialchars($_POST['prompt'] ?? 'Modern minimalist theme with blue accents, card layout') ?></textarea>
        </div>

        <div class="form-group">
            <label for="provider">AI Provider:</label>
            <select name="provider" id="provider">
                <option value="gemini" <?= ($_POST['provider'] ?? 'gemini') === 'gemini' ? 'selected' : '' ?>>Gemini 2.5 Flash</option>
                <option value="openai" <?= ($_POST['provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI GPT-4</option>
                <option value="claude" <?= ($_POST['provider'] ?? '') === 'claude' ? 'selected' : '' ?>>Claude 3.5</option>
            </select>
        </div>

        <button type="submit" class="btn-test">🧪 Test Generation</button>
    </form>

    <?php if ($result): ?>
    <div class="debug-output">
        <div class="output-section">
            <h3>📊 Status:</h3>
            <div class="output-content <?= $result['success'] ? 'success' : 'error' ?>">
                <?= $result['success'] ? '✅ SUCCESS - JSON parsed successfully!' : '❌ FAILED - Could not parse JSON' ?>
                <?php if (!$result['success'] && isset($result['error'])): ?>
                    <br><br><strong>Parse Error:</strong> <?= htmlspecialchars($result['error']) ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($result['raw']): ?>
        <div class="output-section">
            <h3>📄 Raw AI Response (complete):</h3>
            <div class="output-content">
                <?= htmlspecialchars($result['raw']) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($result['extracted_content'])): ?>
        <div class="output-section">
            <h3>🔧 Extracted Content (after cleanup):</h3>
            <div class="output-content">
                <?= htmlspecialchars($result['extracted_content']) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($result['success'] && isset($result['parsed'])): ?>
        <div class="output-section">
            <h3>✨ Parsed JSON (formatted):</h3>
            <div class="output-content">
                <?= htmlspecialchars(json_encode($result['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
