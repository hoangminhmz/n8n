<?php
/**
 * LightBlog CMS - AI Prompt Templates Manager
 * Customize AI prompts for content generation
 */

$pageTitle = 'AI Prompt Templates';
require_once __DIR__ . '/includes/header.php';
require_once SITE_PATH . '/core/AI/PromptManager.php';

$promptManager = new PromptManager();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save all prompts
    if (isset($_POST['save_all'])) {
        $saved = 0;
        $failed = 0;

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'prompt_') === 0) {
                $templateKey = str_replace('prompt_', '', $key);
                $customPrompt = trim($value);
                $isActive = isset($_POST['active_' . $templateKey]) ? 1 : 0;

                try {
                    $promptManager->updateTemplate($templateKey, $customPrompt, $isActive, $_SESSION['user_id']);
                    $saved++;
                } catch (Exception $e) {
                    $failed++;
                }
            }
        }

        $message = "Saved {$saved} prompts successfully" . ($failed > 0 ? " ({$failed} failed)" : "");
        $messageType = $failed > 0 ? 'warning' : 'success';
    }

    // Reset to default
    if (isset($_POST['reset_template'])) {
        $templateKey = $_POST['template_key'];
        try {
            $promptManager->resetToDefault($templateKey);
            $message = "Reset to default successfully";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Failed to reset: " . $e->getMessage();
            $messageType = 'error';
        }
    }

    // Export prompts
    if (isset($_POST['export_prompts'])) {
        $json = $promptManager->exportCustomPrompts();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lightblog-prompts-' . date('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    // Import prompts
    if (isset($_POST['import_prompts']) && isset($_FILES['import_file'])) {
        $file = $_FILES['import_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $json = file_get_contents($file['tmp_name']);
            $result = $promptManager->importCustomPrompts($json, $_SESSION['user_id']);

            $message = "Imported {$result['success']} prompts" .
                ($result['failed'] > 0 ? ", {$result['failed']} failed" : "");
            $messageType = $result['failed'] > 0 ? 'warning' : 'success';

            if (!empty($result['errors'])) {
                $message .= ": " . implode(', ', $result['errors']);
            }
        } else {
            $message = "Upload failed";
            $messageType = 'error';
        }
    }
}

// Get all templates grouped by category
$postTemplates = $promptManager->getTemplatesByCategory('post');
$campaignTemplates = $promptManager->getTemplatesByCategory('campaign');
$imageTemplates = $promptManager->getTemplatesByCategory('image');

// Get statistics
$stats = $promptManager->getStatistics();

?>

<style>
    .prompts-container {
        max-width: 1400px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
    }

    .tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .tab {
        padding: 1rem 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab:hover {
        color: #667eea;
    }

    .tab.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .prompt-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .prompt-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .prompt-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
    }

    .prompt-description {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .prompt-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #667eea;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .variables-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .variable-tag {
        background: #f3f4f6;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.875rem;
        font-family: 'Courier New', monospace;
        color: #667eea;
        border: 1px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s;
    }

    .variable-tag:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .prompt-editor {
        width: 100%;
        min-height: 200px;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        line-height: 1.6;
        resize: vertical;
        transition: border-color 0.3s;
    }

    .prompt-editor:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .prompt-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .char-counter {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .char-counter.warning {
        color: #f59e0b;
    }

    .char-counter.error {
        color: #ef4444;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-secondary:hover {
        background: #e5e7eb;
    }

    .btn-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-danger:hover {
        background: #fecaca;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        border-left: 4px solid;
    }

    .alert-success {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }

    .alert-warning {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #92400e;
    }

    .alert-error {
        background: #fee2e2;
        border-color: #ef4444;
        color: #991b1b;
    }

    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 8px;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 2rem;
        border-radius: 12px;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .close-modal {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #6b7280;
    }

    .close-modal:hover {
        color: #111827;
    }
</style>

<div class="prompts-container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Templates</div>
            <div class="stat-value"><?= $stats['total_templates'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Custom Prompts</div>
            <div class="stat-value"><?= $stats['custom_prompts_created'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Custom</div>
            <div class="stat-value"><?= $stats['custom_prompts_active'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Using Defaults</div>
            <div class="stat-value"><?= $stats['using_defaults'] ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar">
        <div>
            <h2 style="margin: 0;">AI Prompt Templates</h2>
            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                Customize AI prompts to match your content style and requirements
            </p>
        </div>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary" onclick="showImportModal()">
                📥 Import
            </button>
            <form method="POST" style="display: inline;">
                <button type="submit" name="export_prompts" class="btn btn-secondary">
                    📤 Export
                </button>
            </form>
            <button type="submit" form="prompts-form" name="save_all" class="btn btn-primary">
                💾 Save All Changes
            </button>
        </div>
    </div>

    <form id="prompts-form" method="POST">
        <!-- Tabs -->
        <div class="tabs">
            <button type="button" class="tab active" data-tab="post">
                📝 Post Generation (<?= count($postTemplates) ?>)
            </button>
            <button type="button" class="tab" data-tab="campaign">
                🎯 Campaigns (<?= count($campaignTemplates) ?>)
            </button>
            <button type="button" class="tab" data-tab="image">
                🖼️ Images (<?= count($imageTemplates) ?>)
            </button>
        </div>

        <!-- Post Templates Tab -->
        <div class="tab-content active" id="tab-post">
            <?php foreach ($postTemplates as $template):
                $variables = json_decode($template->variables, true) ?? [];
                $currentPrompt = $template->is_active && $template->custom_prompt
                    ? $template->custom_prompt
                    : $template->default_prompt;
            ?>
                <div class="prompt-card">
                    <div class="prompt-header">
                        <div>
                            <div class="prompt-title"><?= htmlspecialchars($template->template_name) ?></div>
                            <div class="prompt-description"><?= htmlspecialchars($template->description) ?></div>
                        </div>
                        <div class="prompt-controls">
                            <span style="font-size: 0.875rem; color: #6b7280; margin-right: 0.5rem;">
                                Use Custom
                            </span>
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="active_<?= $template->template_key ?>"
                                       <?= $template->is_active ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="variables-list">
                        <span style="font-size: 0.875rem; color: #6b7280; margin-right: 0.5rem;">
                            Available variables:
                        </span>
                        <?php foreach ($variables as $var): ?>
                            <span class="variable-tag"
                                  onclick="insertVariable('<?= $template->template_key ?>', '<?= htmlspecialchars($var) ?>')">
                                <?= htmlspecialchars($var) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <textarea class="prompt-editor"
                              name="prompt_<?= $template->template_key ?>"
                              id="editor_<?= $template->template_key ?>"
                              data-max="4000"
                              oninput="updateCharCount('<?= $template->template_key ?>')"><?= htmlspecialchars($currentPrompt) ?></textarea>

                    <div class="prompt-footer">
                        <div class="char-counter" id="counter_<?= $template->template_key ?>">
                            <?= strlen($currentPrompt) ?> / 4000 characters
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary"
                                    onclick="testPrompt('<?= $template->template_key ?>')">
                                🧪 Test
                            </button>
                            <button type="button" class="btn btn-secondary"
                                    onclick="resetPrompt('<?= $template->template_key ?>')">
                                ↺ Reset to Default
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Campaign Templates Tab -->
        <div class="tab-content" id="tab-campaign">
            <?php foreach ($campaignTemplates as $template):
                $variables = json_decode($template->variables, true) ?? [];
                $currentPrompt = $template->is_active && $template->custom_prompt
                    ? $template->custom_prompt
                    : $template->default_prompt;
            ?>
                <div class="prompt-card">
                    <div class="prompt-header">
                        <div>
                            <div class="prompt-title"><?= htmlspecialchars($template->template_name) ?></div>
                            <div class="prompt-description"><?= htmlspecialchars($template->description) ?></div>
                        </div>
                        <div class="prompt-controls">
                            <span style="font-size: 0.875rem; color: #6b7280; margin-right: 0.5rem;">
                                Use Custom
                            </span>
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="active_<?= $template->template_key ?>"
                                       <?= $template->is_active ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="variables-list">
                        <span style="font-size: 0.875rem; color: #6b7280; margin-right: 0.5rem;">
                            Available variables:
                        </span>
                        <?php foreach ($variables as $var): ?>
                            <span class="variable-tag"
                                  onclick="insertVariable('<?= $template->template_key ?>', '<?= htmlspecialchars($var) ?>')">
                                <?= htmlspecialchars($var) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <textarea class="prompt-editor"
                              name="prompt_<?= $template->template_key ?>"
                              id="editor_<?= $template->template_key ?>"
                              data-max="4000"
                              oninput="updateCharCount('<?= $template->template_key ?>')"><?= htmlspecialchars($currentPrompt) ?></textarea>

                    <div class="prompt-footer">
                        <div class="char-counter" id="counter_<?= $template->template_key ?>">
                            <?= strlen($currentPrompt) ?> / 4000 characters
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary"
                                    onclick="testPrompt('<?= $template->template_key ?>')">
                                🧪 Test
                            </button>
                            <button type="button" class="btn btn-secondary"
                                    onclick="resetPrompt('<?= $template->template_key ?>')">
                                ↺ Reset to Default
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Image Templates Tab -->
        <div class="tab-content" id="tab-image">
            <?php foreach ($imageTemplates as $template):
                $variables = json_decode($template->variables, true) ?? [];
                $currentPrompt = $template->is_active && $template->custom_prompt
                    ? $template->custom_prompt
                    : $template->default_prompt;
            ?>
                <div class="prompt-card">
                    <div class="prompt-header">
                        <div>
                            <div class="prompt-title"><?= htmlspecialchars($template->template_name) ?></div>
                            <div class="prompt-description"><?= htmlspecialchars($template->description) ?></div>
                        </div>
                        <div class="prompt-controls">
                            <span style="font-size: 0.875rem; color: #6b7280; margin-right: 0.5rem;">
                                Use Custom
                            </span>
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="active_<?= $template->template_key ?>"
                                       <?= $template->is_active ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="variables-list">
                        <span style="font-size: 0.875rem; color: #6b7280; margin-right: 0.5rem;">
                            Available variables:
                        </span>
                        <?php foreach ($variables as $var): ?>
                            <span class="variable-tag"
                                  onclick="insertVariable('<?= $template->template_key ?>', '<?= htmlspecialchars($var) ?>')">
                                <?= htmlspecialchars($var) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <textarea class="prompt-editor"
                              name="prompt_<?= $template->template_key ?>"
                              id="editor_<?= $template->template_key ?>"
                              data-max="4000"
                              oninput="updateCharCount('<?= $template->template_key ?>')"><?= htmlspecialchars($currentPrompt) ?></textarea>

                    <div class="prompt-footer">
                        <div class="char-counter" id="counter_<?= $template->template_key ?>">
                            <?= strlen($currentPrompt) ?> / 4000 characters
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary"
                                    onclick="testPrompt('<?= $template->template_key ?>')">
                                🧪 Test
                            </button>
                            <button type="button" class="btn btn-secondary"
                                    onclick="resetPrompt('<?= $template->template_key ?>')">
                                ↺ Reset to Default
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeImportModal()">&times;</span>
        <h2>Import Prompts</h2>
        <p style="color: #6b7280; margin-bottom: 1.5rem;">
            Upload a JSON file with custom prompts. Existing prompts will be overwritten.
        </p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="import_file" accept=".json" required
                   style="margin-bottom: 1rem; width: 100%;">
            <div style="text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">
                    Cancel
                </button>
                <button type="submit" name="import_prompts" class="btn btn-primary">
                    Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Test Modal -->
<div id="testModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeTestModal()">&times;</span>
        <h2>Test Prompt</h2>
        <div id="testResult" style="margin-top: 1rem;"></div>
    </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetTab = this.dataset.tab;

        // Update active tab
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        // Update active content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById('tab-' + targetTab).classList.add('active');
    });
});

// Character counter
function updateCharCount(templateKey) {
    const editor = document.getElementById('editor_' + templateKey);
    const counter = document.getElementById('counter_' + templateKey);
    const length = editor.value.length;
    const max = parseInt(editor.dataset.max);

    counter.textContent = length + ' / ' + max + ' characters';

    counter.classList.remove('warning', 'error');
    if (length > max * 0.9) {
        counter.classList.add('warning');
    }
    if (length > max) {
        counter.classList.add('error');
    }
}

// Insert variable
function insertVariable(templateKey, variable) {
    const editor = document.getElementById('editor_' + templateKey);
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    const text = editor.value;

    editor.value = text.substring(0, start) + variable + text.substring(end);
    editor.focus();
    editor.selectionStart = editor.selectionEnd = start + variable.length;

    updateCharCount(templateKey);
}

// Reset prompt
function resetPrompt(templateKey) {
    if (!confirm('Reset this prompt to default? Your custom prompt will be lost.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="reset_template" value="1">' +
                     '<input type="hidden" name="template_key" value="' + templateKey + '">';
    document.body.appendChild(form);
    form.submit();
}

// Test prompt
function testPrompt(templateKey) {
    const editor = document.getElementById('editor_' + templateKey);
    const prompt = editor.value;

    document.getElementById('testResult').innerHTML = '<div style="text-align: center; padding: 2rem;">Testing prompt...</div>';
    document.getElementById('testModal').style.display = 'block';

    fetch('ajax-test-prompt.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            template_key: templateKey,
            prompt: prompt
        })
    })
    .then(response => response.json())
    .then(data => {
        let html = '<h3>Prompt Preview</h3>';
        html += '<div style="background: #f3f4f6; padding: 1rem; border-radius: 6px; font-family: monospace; white-space: pre-wrap; font-size: 0.875rem;">';
        html += escapeHtml(data.preview);
        html += '</div>';

        if (data.warnings && data.warnings.length > 0) {
            html += '<div class="alert alert-warning" style="margin-top: 1rem;">';
            html += '<strong>Warnings:</strong><ul style="margin: 0.5rem 0 0 1.5rem;">';
            data.warnings.forEach(w => {
                html += '<li>' + escapeHtml(w) + '</li>';
            });
            html += '</ul></div>';
        }

        document.getElementById('testResult').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('testResult').innerHTML =
            '<div class="alert alert-error">Test failed: ' + escapeHtml(error.message) + '</div>';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Modal functions
function showImportModal() {
    document.getElementById('importModal').style.display = 'block';
}

function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
}

function closeTestModal() {
    document.getElementById('testModal').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Initialize character counters
document.querySelectorAll('.prompt-editor').forEach(editor => {
    const templateKey = editor.id.replace('editor_', '');
    updateCharCount(templateKey);
});

// Confirm before leaving with unsaved changes
let formChanged = false;
document.getElementById('prompts-form').addEventListener('change', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
    }
});

document.getElementById('prompts-form').addEventListener('submit', function() {
    formChanged = false;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
