<?php
/**
 * LightBlog CMS - AI Theme Customizer
 * Generate and customize themes using AI
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';
require_once SITE_PATH . '/core/Theme/ThemeCustomizer.php';

$pageTitle = 'Theme Customizer';
$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/includes/header.php';

$customizer = new ThemeCustomizer();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Generate theme from AI
    if (isset($_POST['generate_theme'])) {
        $prompt = trim($_POST['theme_prompt'] ?? '');
        $provider = $_POST['ai_provider'] ?? 'gemini';

        if (empty($prompt)) {
            $message = 'Please enter a theme description';
            $messageType = 'error';
        } else {
            $result = $customizer->generateFromPrompt($prompt, $provider, $_SESSION['user_id']);

            if ($result['success']) {
                $message = '✅ Theme generated successfully! Preview it below.';
                $messageType = 'success';
                $_SESSION['preview_theme_id'] = $result['theme_id'];
            } else {
                $message = '❌ Failed to generate theme: ' . $result['error'];
                $messageType = 'error';
            }
        }
    }

    // Activate theme
    if (isset($_POST['activate_theme'])) {
        $themeId = (int)$_POST['theme_id'];
        try {
            $customizer->activateTheme($themeId);
            $message = '✅ Theme activated successfully!';
            $messageType = 'success';
            unset($_SESSION['preview_theme_id']);
        } catch (Exception $e) {
            $message = '❌ Failed to activate theme: ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Delete theme
    if (isset($_POST['delete_theme'])) {
        $themeId = (int)$_POST['theme_id'];
        try {
            $customizer->deleteTheme($themeId);
            $message = '✅ Theme deleted successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = '❌ Failed to delete theme: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$activeTheme = $customizer->getActiveTheme();
$allThemes = $customizer->getAllThemes();
?>

<style>
.theme-customizer {
    max-width: 1400px;
}

.generator-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.generator-card h2 {
    margin-top: 0;
    color: white;
}

.prompt-input {
    width: 100%;
    padding: 1rem;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    font-size: 16px;
    background: rgba(255,255,255,0.1);
    color: white;
    margin-bottom: 1rem;
    resize: vertical;
    min-height: 100px;
}

.prompt-input::placeholder {
    color: rgba(255,255,255,0.6);
}

.prompt-input:focus {
    outline: none;
    border-color: rgba(255,255,255,0.6);
    background: rgba(255,255,255,0.15);
}

.example-prompts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}

.example-prompt {
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.example-prompt:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.theme-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
}

.theme-card.active {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.theme-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.theme-preview {
    height: 150px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.theme-info {
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}

.theme-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.theme-meta {
    font-size: 12px;
    color: #6b7280;
}

.theme-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.color-swatch {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.color-palette {
    display: flex;
    gap: 0.5rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
</style>

<div class="theme-customizer">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- AI Theme Generator -->
    <div class="generator-card">
        <h2>🎨 Generate Theme with AI</h2>
        <p style="opacity: 0.9; margin-bottom: 1.5rem;">
            Describe your desired theme in natural language, and AI will generate a complete custom theme for you.
        </p>

        <form method="POST">
            <textarea
                name="theme_prompt"
                class="prompt-input"
                placeholder="Example: Modern dark theme with blue accents, card layout, professional look..."
                required
            ><?= htmlspecialchars($_POST['theme_prompt'] ?? '') ?></textarea>

            <div style="display: flex; gap: 1rem; align-items: center;">
                <select name="ai_provider" style="padding: 0.75rem; border-radius: 8px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 16px;">
                    <option value="gemini">Gemini 2.5 Flash</option>
                    <option value="openai">OpenAI GPT-4</option>
                    <option value="claude">Claude 3.5</option>
                </select>

                <button type="submit" name="generate_theme" class="btn btn-primary" style="background: white; color: #667eea;">
                    ✨ Generate Theme
                </button>
            </div>
        </form>

        <div class="example-prompts">
            <strong style="opacity: 0.9;">Examples:</strong>
            <span class="example-prompt" onclick="fillPrompt('Minimalist light theme with green accents, list layout')">🌿 Minimalist Green</span>
            <span class="example-prompt" onclick="fillPrompt('Dark professional theme with purple gradient, card layout')">🌙 Dark Purple</span>
            <span class="example-prompt" onclick="fillPrompt('Vibrant colorful theme with orange primary, grid layout')">🎨 Vibrant Orange</span>
            <span class="example-prompt" onclick="fillPrompt('Clean modern theme with blue tones, spacious card layout')">💎 Modern Blue</span>
        </div>
    </div>

    <!-- Active Theme -->
    <?php if ($activeTheme): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🎯 Active Theme</h2>
        </div>

        <div style="padding: 1.5rem;">
            <div style="display: flex; gap: 2rem; align-items: start;">
                <div style="flex: 1;">
                    <h3><?= htmlspecialchars($activeTheme->name) ?></h3>
                    <?php if ($activeTheme->description): ?>
                        <p style="color: #6b7280; margin: 0.5rem 0;">
                            <?= htmlspecialchars($activeTheme->description) ?>
                        </p>
                    <?php endif; ?>
                    <p style="color: #9ca3af; font-size: 14px; margin-top: 1rem;">
                        Layout: <strong><?= htmlspecialchars($activeTheme->layout_style) ?></strong> •
                        Spacing: <strong><?= htmlspecialchars($activeTheme->layout_spacing) ?></strong> •
                        Border: <strong><?= htmlspecialchars($activeTheme->border_radius) ?></strong>
                    </p>
                </div>

                <?php if ($activeTheme->css_variables): ?>
                    <?php $colors = json_decode($activeTheme->css_variables, true); ?>
                    <?php if ($colors): ?>
                    <div class="color-palette">
                        <?php foreach (array_slice($colors, 0, 5) as $name => $color): ?>
                            <div class="color-swatch" style="background-color: <?= htmlspecialchars($color) ?>" title="<?= htmlspecialchars($name) ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Themes -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h2 class="card-title">📚 All Themes (<?= count($allThemes) ?>)</h2>
        </div>

        <?php if (empty($allThemes)): ?>
            <div style="padding: 3rem; text-align: center; color: #6b7280;">
                <p style="font-size: 18px; margin-bottom: 0.5rem;">No themes yet</p>
                <p>Generate your first theme above!</p>
            </div>
        <?php else: ?>
            <div class="themes-grid" style="padding: 1.5rem;">
                <?php foreach ($allThemes as $theme): ?>
                    <div class="theme-card <?= $theme->is_active ? 'active' : '' ?>">
                        <div class="theme-preview" style="background: linear-gradient(135deg, <?= $theme->css_variables ? json_decode($theme->css_variables, true)['primary'] ?? '#667eea' : '#667eea' ?>, <?= $theme->css_variables ? json_decode($theme->css_variables, true)['secondary'] ?? '#764ba2' : '#764ba2' ?>);">
                            <?php if ($theme->css_variables): ?>
                                <?php $colors = json_decode($theme->css_variables, true); ?>
                                <div class="color-palette">
                                    <?php foreach (array_slice($colors, 0, 6) as $color): ?>
                                        <div class="color-swatch" style="background-color: <?= htmlspecialchars($color) ?>; width: 30px; height: 30px;"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="theme-info">
                            <div class="theme-name">
                                <?= htmlspecialchars($theme->name) ?>
                                <?php if ($theme->is_active): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php elseif ($theme->is_preview): ?>
                                    <span class="badge badge-info">Preview</span>
                                <?php endif; ?>
                            </div>
                            <div class="theme-meta">
                                <?= htmlspecialchars($theme->layout_style) ?> layout •
                                Created <?= date('M j, Y', strtotime($theme->created_at)) ?>
                            </div>
                        </div>

                        <div class="theme-actions">
                            <?php if (!$theme->is_active): ?>
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="theme_id" value="<?= $theme->id ?>">
                                    <button type="submit" name="activate_theme" class="btn btn-primary" style="width: 100%;">
                                        Activate
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (!$theme->is_active): ?>
                                <form method="POST" onsubmit="return confirm('Delete this theme?');">
                                    <input type="hidden" name="theme_id" value="<?= $theme->id ?>">
                                    <button type="submit" name="delete_theme" class="btn btn-secondary">
                                        🗑️
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function fillPrompt(text) {
    document.querySelector('[name="theme_prompt"]').value = text;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
