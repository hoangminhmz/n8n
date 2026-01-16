<?php
/**
 * Test AI Providers (Gemini, OpenAI, Claude)
 * Checks if API keys work and response quality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120); // 2 minutes for AI requests

require_once __DIR__ . '/../config.php';

// Get API keys from config
$geminiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
$openaiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
$claudeKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Providers Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .provider { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .provider h2 { margin-top: 0; color: #333; }
        .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .response { background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin: 10px 0; font-family: monospace; white-space: pre-wrap; font-size: 13px; max-height: 400px; overflow-y: auto; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 10px 0; }
        .stat { background: #f8f9fa; padding: 10px; border-radius: 4px; text-align: center; }
        .stat-label { font-size: 12px; color: #6c757d; }
        .stat-value { font-size: 24px; font-weight: bold; color: #333; }
        button { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        button:hover { background: #5568d3; }
        button:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 AI Providers Test</h1>
        <p>Test API connectivity and response quality for all configured AI providers.</p>

        <!-- Google Gemini -->
        <div class="provider">
            <h2>🔵 Google Gemini</h2>

            <?php if (empty($geminiKey)): ?>
                <div class="status error">❌ API Key not configured</div>
            <?php else: ?>
                <div class="status info">✓ API Key: <?= substr($geminiKey, 0, 8) ?>...<?= substr($geminiKey, -4) ?></div>

                <button onclick="testGemini()">Test Gemini API</button>
                <div id="gemini-result"></div>
            <?php endif; ?>
        </div>

        <!-- OpenAI -->
        <div class="provider">
            <h2>🟢 OpenAI</h2>

            <?php if (empty($openaiKey)): ?>
                <div class="status error">❌ API Key not configured</div>
            <?php else: ?>
                <div class="status info">✓ API Key: <?= substr($openaiKey, 0, 8) ?>...<?= substr($openaiKey, -4) ?></div>

                <button onclick="testOpenAI()">Test OpenAI API</button>
                <div id="openai-result"></div>
            <?php endif; ?>
        </div>

        <!-- Claude (Anthropic) -->
        <div class="provider">
            <h2>🟣 Claude (Anthropic)</h2>

            <?php if (empty($claudeKey)): ?>
                <div class="status error">❌ API Key not configured</div>
            <?php else: ?>
                <div class="status info">✓ API Key: <?= substr($claudeKey, 0, 8) ?>...<?= substr($claudeKey, -4) ?></div>

                <button onclick="testClaude()">Test Claude API</button>
                <div id="claude-result"></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function testGemini() {
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Testing...';

        const resultDiv = document.getElementById('gemini-result');
        resultDiv.innerHTML = '<div class="status info">⏳ Sending request to Gemini API...</div>';

        fetch('test-ai-providers-ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                provider: 'gemini',
                test_type: 'full'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Test Gemini API';

            if (data.success) {
                let html = '<div class="status success">✅ ' + data.message + '</div>';

                html += '<div class="stats">';
                html += '<div class="stat"><div class="stat-label">Response Time</div><div class="stat-value">' + data.stats.response_time + 's</div></div>';
                html += '<div class="stat"><div class="stat-label">Characters</div><div class="stat-value">' + data.stats.characters + '</div></div>';
                html += '<div class="stat"><div class="stat-label">Model</div><div class="stat-value">' + data.stats.model + '</div></div>';
                html += '</div>';

                html += '<h4>Response:</h4><div class="response">' + escapeHtml(data.response) + '</div>';

                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = '<div class="status error">❌ Error: ' + escapeHtml(data.message) + '</div>' +
                    (data.details ? '<div class="response">' + escapeHtml(data.details) + '</div>' : '');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Test Gemini API';
            resultDiv.innerHTML = '<div class="status error">❌ Request failed: ' + escapeHtml(err.message) + '</div>';
        });
    }

    function testOpenAI() {
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Testing...';

        const resultDiv = document.getElementById('openai-result');
        resultDiv.innerHTML = '<div class="status info">⏳ Sending request to OpenAI API...</div>';

        fetch('test-ai-providers-ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                provider: 'openai',
                test_type: 'full'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Test OpenAI API';

            if (data.success) {
                let html = '<div class="status success">✅ ' + data.message + '</div>';

                html += '<div class="stats">';
                html += '<div class="stat"><div class="stat-label">Response Time</div><div class="stat-value">' + data.stats.response_time + 's</div></div>';
                html += '<div class="stat"><div class="stat-label">Characters</div><div class="stat-value">' + data.stats.characters + '</div></div>';
                html += '<div class="stat"><div class="stat-label">Model</div><div class="stat-value">' + data.stats.model + '</div></div>';
                html += '</div>';

                html += '<h4>Response:</h4><div class="response">' + escapeHtml(data.response) + '</div>';

                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = '<div class="status error">❌ Error: ' + escapeHtml(data.message) + '</div>' +
                    (data.details ? '<div class="response">' + escapeHtml(data.details) + '</div>' : '');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Test OpenAI API';
            resultDiv.innerHTML = '<div class="status error">❌ Request failed: ' + escapeHtml(err.message) + '</div>';
        });
    }

    function testClaude() {
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Testing...';

        const resultDiv = document.getElementById('claude-result');
        resultDiv.innerHTML = '<div class="status info">⏳ Sending request to Claude API...</div>';

        fetch('test-ai-providers-ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                provider: 'claude',
                test_type: 'full'
            })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Test Claude API';

            if (data.success) {
                let html = '<div class="status success">✅ ' + data.message + '</div>';

                html += '<div class="stats">';
                html += '<div class="stat"><div class="stat-label">Response Time</div><div class="stat-value">' + data.stats.response_time + 's</div></div>';
                html += '<div class="stat"><div class="stat-label">Characters</div><div class="stat-value">' + data.stats.characters + '</div></div>';
                html += '<div class="stat"><div class="stat-label">Model</div><div class="stat-value">' + data.stats.model + '</div></div>';
                html += '</div>';

                html += '<h4>Response:</h4><div class="response">' + escapeHtml(data.response) + '</div>';

                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = '<div class="status error">❌ Error: ' + escapeHtml(data.message) + '</div>' +
                    (data.details ? '<div class="response">' + escapeHtml(data.details) + '</div>' : '');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Test Claude API';
            resultDiv.innerHTML = '<div class="status error">❌ Request failed: ' + escapeHtml(err.message) + '</div>';
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>
