<?php
/**
 * LightBlog CMS - Settings
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Settings';
$auth = new Auth();
$auth->requireAdmin();

$db = Database::getInstance();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Update settings in database
    $settingsToUpdate = [
        'site_name', 'site_tagline', 'posts_per_page', 'timezone',
        'openai_api_key', 'claude_api_key', 'gemini_api_key', 'unsplash_api_key',
        'image_ai_provider', 'content_ai_provider',
        'autoblog_enabled'
    ];

    foreach ($settingsToUpdate as $setting) {
        if (isset($_POST[$setting])) {
            $exists = $db->queryOne("SELECT * FROM settings WHERE `key` = ?", [$setting]);

            if ($exists) {
                $db->update('settings', ['value' => $_POST[$setting]], '`key` = :key', ['key' => $setting]);
            } else {
                $db->insert('settings', ['key' => $setting, 'value' => $_POST[$setting]]);
            }
        }
    }

    // Update config.php file
    updateConfigFile($_POST);

    $message = 'Settings saved successfully!';
}

// Load current settings
function getSetting($key, $default = '') {
    global $db;
    $setting = $db->queryOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $setting ? $setting->value : $default;
}

// Update config.php
function updateConfigFile($data) {
    $configPath = __DIR__ . '/../config.php';
    $content = file_get_contents($configPath);

    $updates = [
        'OPENAI_API_KEY' => $data['openai_api_key'] ?? '',
        'CLAUDE_API_KEY' => $data['claude_api_key'] ?? '',
        'GEMINI_API_KEY' => $data['gemini_api_key'] ?? '',
        'UNSPLASH_API_KEY' => $data['unsplash_api_key'] ?? '',
        'AUTOBLOG_ENABLED' => isset($data['autoblog_enabled']) && $data['autoblog_enabled'] ? 'true' : 'false'
    ];

    foreach ($updates as $constant => $value) {
        $pattern = "/define\('{$constant}',\s*'[^']*'\);/";
        $replacement = "define('{$constant}', '{$value}');";
        $content = preg_replace($pattern, $replacement, $content);
    }

    file_put_contents($configPath, $content);
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Site Settings</h2>
    </div>

    <form method="POST">
        <h3 style="margin-bottom: 1rem;">General Settings</h3>

        <div class="form-row">
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?= htmlspecialchars(getSetting('site_name', 'My Blog')) ?>">
            </div>
            <div class="form-group">
                <label>Site Tagline</label>
                <input type="text" name="site_tagline" value="<?= htmlspecialchars(getSetting('site_tagline', 'AI-Powered Content Hub')) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Posts Per Page</label>
                <input type="number" name="posts_per_page" value="<?= htmlspecialchars(getSetting('posts_per_page', '10')) ?>" min="1">
            </div>
            <div class="form-group">
                <label>Timezone</label>
                <select name="timezone">
                    <?php
                    $timezones = ['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Asia/Tokyo'];
                    $current = getSetting('timezone', 'UTC');
                    foreach ($timezones as $tz) {
                        $selected = $tz === $current ? 'selected' : '';
                        echo "<option value=\"{$tz}\" {$selected}>{$tz}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <hr style="margin: 2rem 0;">
        <h3 style="margin-bottom: 1rem;">AI Auto-Blogging</h3>

        <div class="form-group">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="autoblog_enabled" value="1"
                    <?= getSetting('autoblog_enabled') ? 'checked' : '' ?>
                    style="width: auto; margin-right: 0.5rem;">
                Enable AI Auto-Blogging Features
            </label>
            <small>Requires API keys for AI providers</small>
        </div>

        <div class="form-group">
            <label>OpenAI API Key</label>
            <input type="password" name="openai_api_key"
                value="<?= htmlspecialchars(getSetting('openai_api_key', defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '')) ?>"
                placeholder="sk-...">
            <small>Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></small>
        </div>

        <div class="form-group">
            <label>Anthropic Claude API Key</label>
            <input type="password" name="claude_api_key"
                value="<?= htmlspecialchars(getSetting('claude_api_key', defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '')) ?>"
                placeholder="sk-ant-...">
            <small>Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></small>
        </div>

        <div class="form-group">
            <label>Google Gemini API Key</label>
            <input type="password" name="gemini_api_key"
                value="<?= htmlspecialchars(getSetting('gemini_api_key', defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '')) ?>"
                placeholder="AIza...">
            <small>Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></small>
        </div>

        <div class="form-group">
            <label>Unsplash API Key (Free Stock Photos)</label>
            <input type="password" name="unsplash_api_key"
                value="<?= htmlspecialchars(getSetting('unsplash_api_key', defined('UNSPLASH_API_KEY') ? UNSPLASH_API_KEY : '')) ?>"
                placeholder="Access Key...">
            <small>Get your free API key from <a href="https://unsplash.com/developers" target="_blank">Unsplash Developers</a> (500 requests/hour)</small>
        </div>

        <hr style="margin: 2rem 0;">
        <h3 style="margin-bottom: 1rem;">AI Provider Selection</h3>

        <div class="form-row">
            <div class="form-group">
                <label>Image Generation Provider</label>
                <select name="image_ai_provider">
                    <?php
                    $imageProviders = [
                        'auto' => 'Auto-detect (Unsplash → OpenAI → PHP GD)',
                        'unsplash' => 'Unsplash (Free stock photos)',
                        'openai' => 'OpenAI DALL-E 3 ($0.04/image)',
                        'php-gd' => 'PHP GD (Free, text overlay)'
                    ];
                    $currentImage = getSetting('image_ai_provider', 'auto');
                    foreach ($imageProviders as $value => $label) {
                        $selected = $value === $currentImage ? 'selected' : '';
                        echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                    }
                    ?>
                </select>
                <small>Used for generating post thumbnails/featured images</small>
            </div>

            <div class="form-group">
                <label>Content Generation Provider</label>
                <select name="content_ai_provider">
                    <?php
                    $contentProviders = [
                        'auto' => 'Auto-detect (Gemini → Claude → OpenAI)',
                        'gemini' => 'Google Gemini (Cheapest, fast)',
                        'claude' => 'Anthropic Claude (Balanced)',
                        'openai' => 'OpenAI GPT-4 (Most capable)'
                    ];
                    $currentContent = getSetting('content_ai_provider', 'auto');
                    foreach ($contentProviders as $value => $label) {
                        $selected = $value === $currentContent ? 'selected' : '';
                        echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                    }
                    ?>
                </select>
                <small>Used for AI content generation and auto-blogging</small>
            </div>
        </div>

        <div class="alert alert-info">
            💡 <strong>Tip:</strong> You only need to configure the AI provider you plan to use. Start with one to test, then add more as needed.
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<!-- System Information -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">System Information</h2>
    </div>

    <table style="width: 100%;">
        <tr>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><strong>PHP Version</strong></td>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><?= PHP_VERSION ?></td>
        </tr>
        <tr>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><strong>Database Type</strong></td>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><?= DB_TYPE ?></td>
        </tr>
        <tr>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><strong>Site URL</strong></td>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><?= SITE_URL ?></td>
        </tr>
        <tr>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><strong>Cache Enabled</strong></td>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><?= CACHE_ENABLED ? 'Yes' : 'No' ?></td>
        </tr>
        <tr>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><strong>Debug Mode</strong></td>
            <td style="padding: 0.75rem; border-top: 1px solid var(--border);"><?= DEBUG_MODE ? 'Enabled' : 'Disabled' ?></td>
        </tr>
    </table>
</div>

<!-- Cache Management -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Cache Management</h2>
    </div>

    <?php
    $cache = new Cache();
    $cacheStats = $cache->getStats();
    ?>

    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 1.5rem;">
        <div>
            <div class="stat-label">Total Files</div>
            <div class="stat-value"><?= $cacheStats['total_files'] ?></div>
        </div>
        <div>
            <div class="stat-label">Valid Files</div>
            <div class="stat-value"><?= $cacheStats['valid_files'] ?></div>
        </div>
        <div>
            <div class="stat-label">Total Size</div>
            <div class="stat-value"><?= $cacheStats['total_size_mb'] ?> MB</div>
        </div>
    </div>

    <div style="display: flex; gap: 1rem;">
        <a href="?clear_cache=1" class="btn btn-danger" onclick="return confirm('Clear all cache?');">Clear All Cache</a>
        <a href="?clear_expired=1" class="btn btn-outline">Clear Expired Only</a>
    </div>

    <?php
    if (isset($_GET['clear_cache'])) {
        $cleared = $cache->clear();
        echo "<div class='alert alert-success' style='margin-top: 1rem;'>Cleared {$cleared} cache files</div>";
    } elseif (isset($_GET['clear_expired'])) {
        $cleared = $cache->clearExpired();
        echo "<div class='alert alert-success' style='margin-top: 1rem;'>Cleared {$cleared} expired cache files</div>";
    }
    ?>
</div>

<!-- Backup & Restore -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Backup & Restore</h2>
    </div>

    <div class="alert alert-warning">
        ⚠️ <strong>Important:</strong> Backup includes source code and database. API keys will be removed for security. After restore, you'll need to re-enter API keys in Settings.
    </div>

    <!-- Backup Section -->
    <div style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;">Create Backup</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
            Create a complete backup of your site including all source code and database. The backup will be downloaded as a ZIP file.
        </p>

        <button type="button" id="createBackupBtn" class="btn btn-primary">
            <span id="backupBtnText">Create Backup</span>
            <span id="backupSpinner" style="display: none;">⏳ Creating backup...</span>
        </button>

        <div id="backupResult" style="margin-top: 1rem;"></div>
    </div>

    <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border);">

    <!-- Restore Section -->
    <div>
        <h3 style="margin-bottom: 1rem;">Restore from Backup</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
            Upload a backup ZIP file to restore your site. <strong>WARNING:</strong> This will overwrite all current data!
        </p>

        <div class="alert alert-danger" style="margin-bottom: 1rem;">
            🚨 <strong>Danger Zone:</strong> Restore will replace ALL current data. Make sure you have a recent backup before proceeding.
        </div>

        <form id="restoreForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select Backup File (ZIP)</label>
                <input type="file" id="backupFileInput" name="backup_file" accept=".zip" required>
                <small>Maximum file size: 500MB</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="confirmRestore" required>
                    I understand this will overwrite all current data
                </label>
            </div>

            <button type="submit" class="btn btn-danger">
                <span id="restoreBtnText">Restore from Backup</span>
                <span id="restoreSpinner" style="display: none;">⏳ Restoring...</span>
            </button>
        </form>

        <div id="restoreResult" style="margin-top: 1rem;"></div>
    </div>
</div>

<style>
.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.alert-warning {
    background-color: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
}
.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}
</style>

<script>
// Create Backup
document.getElementById('createBackupBtn').addEventListener('click', async function() {
    const btn = this;
    const btnText = document.getElementById('backupBtnText');
    const spinner = document.getElementById('backupSpinner');
    const resultDiv = document.getElementById('backupResult');

    // Disable button
    btn.disabled = true;
    btnText.style.display = 'none';
    spinner.style.display = 'inline';
    resultDiv.innerHTML = '';

    try {
        const response = await fetch('backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=create_backup'
        });

        const result = await response.json();

        if (result.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    ✅ Backup created successfully!<br>
                    <strong>Size:</strong> ${(result.size / 1024 / 1024).toFixed(2)} MB<br>
                    <strong>Date:</strong> ${result.metadata.date}<br>
                    <a href="backup.php?action=download_backup" class="btn btn-primary" style="margin-top: 0.5rem;">
                        Download Backup
                    </a>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    ❌ Backup failed: ${result.message}
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                ❌ Error: ${error.message}
            </div>
        `;
    } finally {
        btn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
    }
});

// Restore from Backup
document.getElementById('restoreForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const confirmRestore = document.getElementById('confirmRestore');
    if (!confirmRestore.checked) {
        alert('Please confirm that you understand the restore will overwrite all data.');
        return;
    }

    if (!confirm('⚠️ FINAL WARNING: This will replace ALL your current data. Are you absolutely sure?')) {
        return;
    }

    const fileInput = document.getElementById('backupFileInput');
    const restoreBtnText = document.getElementById('restoreBtnText');
    const restoreSpinner = document.getElementById('restoreSpinner');
    const resultDiv = document.getElementById('restoreResult');
    const submitBtn = this.querySelector('button[type="submit"]');

    // Disable form
    submitBtn.disabled = true;
    restoreBtnText.style.display = 'none';
    restoreSpinner.style.display = 'inline';
    resultDiv.innerHTML = '';

    const formData = new FormData();
    formData.append('action', 'restore_backup');
    formData.append('backup_file', fileInput.files[0]);

    try {
        const response = await fetch('backup.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    ✅ Restore completed successfully!<br>
                    <strong>Restored from:</strong> ${result.metadata.date}<br>
                    <strong>Version:</strong> ${result.metadata.version}<br><br>
                    ⚠️ <strong>Important:</strong> Please re-enter your API keys in Settings above.<br>
                    <button onclick="location.reload()" class="btn btn-primary" style="margin-top: 0.5rem;">
                        Reload Page
                    </button>
                </div>
            `;

            // Reset form
            this.reset();
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    ❌ Restore failed: ${result.message}
                    ${result.rolled_back ? '<br><br>✅ Database has been rolled back to previous state.' : ''}
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                ❌ Error: ${error.message}
            </div>
        `;
    } finally {
        submitBtn.disabled = false;
        restoreBtnText.style.display = 'inline';
        restoreSpinner.style.display = 'none';
    }
});

// File size validation
document.getElementById('backupFileInput').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const maxSize = 500 * 1024 * 1024; // 500MB
        if (file.size > maxSize) {
            alert('File size exceeds 500MB limit. Please choose a smaller file.');
            this.value = '';
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
