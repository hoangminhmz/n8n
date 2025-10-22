<?php
/**
 * LightBlog CMS - Permission Fixer
 * This script helps fix file permissions for the content directory
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Permissions - LightBlog</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }
        h1 { color: #667eea; margin-bottom: 1rem; }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover { opacity: 0.9; }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
        .result { margin: 1rem 0; padding: 1rem; background: #f5f5f5; border-radius: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 LightBlog Permission Fixer</h1>

        <?php
        if (isset($_POST['fix_permissions'])):
            $results = [];
            $errors = [];

            // Directories to fix
            $directories = [
                __DIR__ . '/content',
                __DIR__ . '/content/uploads',
                __DIR__ . '/content/cache',
                __DIR__ . '/content/database',
                __DIR__ . '/content/ai-queue',
                __DIR__ . '/content/templates',
                __DIR__ . '/content/campaigns'
            ];

            foreach ($directories as $dir) {
                // Create directory if it doesn't exist
                if (!is_dir($dir)) {
                    if (@mkdir($dir, 0755, true)) {
                        $results[] = "✓ Created: " . basename($dir);
                    } else {
                        $errors[] = "✗ Failed to create: " . basename($dir);
                    }
                }

                // Set permissions
                if (is_dir($dir)) {
                    if (@chmod($dir, 0755)) {
                        $results[] = "✓ Fixed permissions: " . basename($dir);
                    } else {
                        $errors[] = "✗ Could not change permissions: " . basename($dir);
                    }
                }
            }

            // Create .htaccess to protect sensitive files
            $htaccessContent = "# Protect sensitive files\n";
            $htaccessContent .= "Options -Indexes\n";
            $htaccessContent .= "<Files \"*.db\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</Files>\n";

            @file_put_contents(__DIR__ . '/content/.htaccess', $htaccessContent);

            if (empty($errors)):
        ?>
                <div class="alert alert-success">
                    <h3>✅ Success!</h3>
                    <p>Permissions have been fixed successfully.</p>
                </div>

                <div class="result">
                    <strong>Actions completed:</strong>
                    <ul>
                        <?php foreach ($results as $result): ?>
                            <li><?= htmlspecialchars($result) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <p style="margin: 1.5rem 0;">
                    <a href="/install.php" class="btn">Continue to Installation →</a>
                </p>

            <?php else: ?>
                <div class="alert alert-error">
                    <h3>⚠️ Some Issues Occurred</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <strong>Manual Fix Required:</strong>
                    <p>Please run this command via SSH/Terminal:</p>
                    <pre>chmod -R 755 <?= __DIR__ ?>/content/</pre>
                    <p style="margin-top: 0.5rem;">Or contact your hosting provider for assistance.</p>
                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="alert alert-info">
                <p><strong>This script will:</strong></p>
                <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                    <li>Create necessary directories if missing</li>
                    <li>Set proper permissions (755) on all directories</li>
                    <li>Add security protection to sensitive files</li>
                </ul>
            </div>

            <form method="POST">
                <p style="margin: 1.5rem 0;">
                    <button type="submit" name="fix_permissions" class="btn">
                        🔧 Fix Permissions Now
                    </button>
                </p>
            </form>

            <hr style="margin: 2rem 0;">

            <details>
                <summary style="cursor: pointer; color: #667eea; font-weight: 500;">
                    📖 Manual Fix Instructions
                </summary>

                <div style="margin-top: 1rem;">
                    <h3 style="margin-bottom: 0.5rem;">Via FTP/cPanel File Manager:</h3>
                    <ol style="margin-left: 1.5rem;">
                        <li>Navigate to your LightBlog installation folder</li>
                        <li>Right-click on the <strong>content</strong> folder</li>
                        <li>Select "File Permissions" or "CHMOD"</li>
                        <li>Set permissions to <strong>755</strong></li>
                        <li>Check "Apply to subdirectories"</li>
                        <li>Click OK/Apply</li>
                    </ol>

                    <h3 style="margin: 1rem 0 0.5rem;">Via Terminal/SSH:</h3>
                    <pre>cd <?= __DIR__ ?>
chmod -R 755 content/</pre>

                    <p style="margin-top: 1rem; color: #666;">
                        <strong>Note:</strong> If 755 doesn't work, try 775 or 777 (less secure but works on some servers)
                    </p>
                </div>
            </details>

        <?php endif; ?>
    </div>
</body>
</html>
