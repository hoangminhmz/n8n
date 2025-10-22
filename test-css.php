<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Test</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/themes/default/style.css">
    <style>
        /* Inline test style */
        .test-inline {
            background: yellow;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>CSS Loading Test</h1>

    <div class="test-inline">
        <strong>Test 1: Inline CSS</strong><br>
        If you see YELLOW background, inline CSS works ✓
    </div>

    <div class="container">
        <strong>Test 2: External CSS (.container)</strong><br>
        If this text is centered with max-width 1200px and padding, external CSS works ✓<br>
        Otherwise, external CSS is NOT loading ✗
    </div>

    <div class="post-card" style="max-width: 400px; margin: 20px 0;">
        <div class="post-card-content">
            <h2>Test 3: Post Card</h2>
            <p>If this card has border, padding, and rounded corners, CSS is working ✓</p>
        </div>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #f0f0f0;">
        <h2>CSS Diagnostics:</h2>
        <pre id="diagnostics">Checking...</pre>
    </div>

    <script>
        // Check if CSS is loaded
        const diagnostics = document.getElementById('diagnostics');
        let report = '';

        // Test 1: Check if stylesheet is loaded
        const stylesheets = document.styleSheets;
        report += 'Total stylesheets loaded: ' + stylesheets.length + '\n\n';

        for (let i = 0; i < stylesheets.length; i++) {
            try {
                const sheet = stylesheets[i];
                report += 'Stylesheet ' + (i + 1) + ':\n';
                report += '  href: ' + (sheet.href || 'inline') + '\n';
                report += '  rules: ' + (sheet.cssRules ? sheet.cssRules.length : 'N/A') + '\n\n';
            } catch (e) {
                report += 'Stylesheet ' + (i + 1) + ': Error accessing rules\n\n';
            }
        }

        // Test 2: Check computed styles
        const container = document.querySelector('.container');
        if (container) {
            const styles = window.getComputedStyle(container);
            report += 'Computed styles for .container:\n';
            report += '  max-width: ' + styles.maxWidth + '\n';
            report += '  margin: ' + styles.margin + '\n';
            report += '  padding: ' + styles.padding + '\n\n';
        }

        const postCard = document.querySelector('.post-card');
        if (postCard) {
            const styles = window.getComputedStyle(postCard);
            report += 'Computed styles for .post-card:\n';
            report += '  border: ' + styles.border + '\n';
            report += '  border-radius: ' + styles.borderRadius + '\n';
            report += '  padding: ' + styles.padding + '\n\n';
        }

        // Test 3: Check if specific CSS variables are defined
        const root = document.documentElement;
        const rootStyles = window.getComputedStyle(root);
        report += 'CSS Variables:\n';
        report += '  --primary: ' + rootStyles.getPropertyValue('--primary') + '\n';
        report += '  --text: ' + rootStyles.getPropertyValue('--text') + '\n';
        report += '  --border: ' + rootStyles.getPropertyValue('--border') + '\n\n';

        // Display results
        diagnostics.textContent = report;

        // Summary
        const hasCSSRules = Array.from(stylesheets).some(sheet => {
            try {
                return sheet.cssRules && sheet.cssRules.length > 0;
            } catch (e) {
                return false;
            }
        });

        if (hasCSSRules) {
            diagnostics.textContent += '\n✓ CSS IS LOADING!\n';
            diagnostics.textContent += 'If page still looks unstyled, there may be a CSS specificity or selector issue.';
        } else {
            diagnostics.textContent += '\n✗ CSS IS NOT LOADING!\n';
            diagnostics.textContent += 'Check browser console for errors.';
        }
    </script>
</body>
</html>
