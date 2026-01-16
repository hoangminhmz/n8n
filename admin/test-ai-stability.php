<?php
/**
 * AI Stability Test - Run multiple tests to measure success rate
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

require_once __DIR__ . '/../config.php';

$testCount = isset($_GET['count']) ? (int)$_GET['count'] : 5;
$provider = isset($_GET['provider']) ? $_GET['provider'] : 'gemini';
?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Stability Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .test-result { padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #ccc; }
        .test-result.success { background: #d4edda; border-color: #28a745; }
        .test-result.error { background: #f8d7da; border-color: #dc3545; }
        .stats { background: #e7f3ff; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 15px; }
        .stat-box { background: white; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 14px; color: #6c757d; margin-top: 5px; }
        .progress { background: #e9ecef; height: 30px; border-radius: 4px; overflow: hidden; margin: 20px 0; }
        .progress-bar { background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; transition: width 0.3s; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        button { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #5568d3; }
        .time { color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 AI Stability Test</h1>
        <p>Run multiple API calls to measure reliability and performance.</p>

        <form method="GET" style="margin-bottom: 30px;">
            <label>Provider:
                <select name="provider" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="gemini" <?= $provider === 'gemini' ? 'selected' : '' ?>>Gemini</option>
                    <option value="openai" <?= $provider === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                    <option value="claude" <?= $provider === 'claude' ? 'selected' : '' ?>>Claude</option>
                </select>
            </label>

            <label style="margin-left: 20px;">Number of tests:
                <select name="count" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="3">3 tests</option>
                    <option value="5" <?= $testCount === 5 ? 'selected' : '' ?>>5 tests</option>
                    <option value="10" <?= $testCount === 10 ? 'selected' : '' ?>>10 tests</option>
                    <option value="20" <?= $testCount === 20 ? 'selected' : '' ?>>20 tests</option>
                </select>
            </label>

            <button type="submit" style="margin-left: 20px;">Start Test</button>
        </form>

        <?php if (isset($_GET['count'])): ?>
            <div id="progress-container">
                <div class="progress">
                    <div class="progress-bar" id="progress-bar" style="width: 0%">0%</div>
                </div>
            </div>

            <div id="results-container"></div>

            <div id="final-stats" style="display: none;">
                <div class="stats">
                    <h2>📊 Test Results</h2>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value" id="success-count">0</div>
                            <div class="stat-label">Successful</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="error-count">0</div>
                            <div class="stat-label">Failed</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="success-rate">0%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="avg-time">0s</div>
                            <div class="stat-label">Avg Response Time</div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            const totalTests = <?= $testCount ?>;
            const provider = '<?= $provider ?>';
            let completedTests = 0;
            let successCount = 0;
            let errorCount = 0;
            let totalTime = 0;

            async function runTest(testNumber) {
                const startTime = Date.now();
                const resultsContainer = document.getElementById('results-container');

                try {
                    const response = await fetch('test-ai-providers-ajax.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            provider: provider,
                            test_type: 'full'
                        })
                    });

                    const data = await response.json();
                    const endTime = Date.now();
                    const duration = ((endTime - startTime) / 1000).toFixed(2);
                    totalTime += parseFloat(duration);

                    let resultHtml = '<div class="test-result ';

                    if (data.success) {
                        successCount++;
                        resultHtml += 'success">✅ <strong>Test #' + testNumber + ' - SUCCESS</strong><br>';
                        resultHtml += '<span class="time">Response time: ' + duration + 's</span><br>';
                        resultHtml += 'Characters: ' + data.stats.characters;
                    } else {
                        errorCount++;
                        resultHtml += 'error">❌ <strong>Test #' + testNumber + ' - FAILED</strong><br>';
                        resultHtml += '<span class="time">Duration: ' + duration + 's</span><br>';
                        resultHtml += 'Error: ' + data.message.substring(0, 100);
                    }

                    resultHtml += '</div>';
                    resultsContainer.innerHTML += resultHtml;

                } catch (error) {
                    errorCount++;
                    const endTime = Date.now();
                    const duration = ((endTime - startTime) / 1000).toFixed(2);

                    resultsContainer.innerHTML += '<div class="test-result error">❌ <strong>Test #' + testNumber + ' - NETWORK ERROR</strong><br>' +
                        '<span class="time">Duration: ' + duration + 's</span><br>' +
                        'Error: ' + error.message + '</div>';
                }

                completedTests++;
                updateProgress();

                if (completedTests === totalTests) {
                    showFinalStats();
                }
            }

            function updateProgress() {
                const percentage = Math.round((completedTests / totalTests) * 100);
                const progressBar = document.getElementById('progress-bar');
                progressBar.style.width = percentage + '%';
                progressBar.textContent = percentage + '%';
            }

            function showFinalStats() {
                const successRate = Math.round((successCount / totalTests) * 100);
                const avgTime = (totalTime / totalTests).toFixed(2);

                document.getElementById('success-count').textContent = successCount;
                document.getElementById('error-count').textContent = errorCount;
                document.getElementById('success-rate').textContent = successRate + '%';
                document.getElementById('avg-time').textContent = avgTime + 's';
                document.getElementById('final-stats').style.display = 'block';

                // Scroll to stats
                document.getElementById('final-stats').scrollIntoView({ behavior: 'smooth' });
            }

            // Run tests sequentially with delay
            async function runAllTests() {
                for (let i = 1; i <= totalTests; i++) {
                    await runTest(i);
                    // Wait 1 second between tests to avoid rate limiting
                    if (i < totalTests) {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }
                }
            }

            // Start tests when page loads
            runAllTests();
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
