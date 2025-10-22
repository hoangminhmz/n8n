<?php
/**
 * LightBlog CMS Installation Wizard
 */

// Start session BEFORE any HTML output
session_start();

// Prevent caching to ensure fresh session data
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LightBlog Installation</title>
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
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; }
        .content { padding: 2rem; }
        .step { display: none; }
        .step.active { display: block; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #666;
        }
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e0e0e0; color: #333; margin-right: 1rem; }
        .btn-group { margin-top: 2rem; }
        .check-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            background: #f5f5f5;
        }
        .check-item.success { background: #d4edda; }
        .check-item.error { background: #f8d7da; }
        .check-icon {
            width: 24px;
            height: 24px;
            margin-right: 1rem;
            font-weight: bold;
        }
        .success .check-icon { color: #28a745; }
        .error .check-icon { color: #dc3545; }
        .progress {
            height: 4px;
            background: #e0e0e0;
            margin-bottom: 2rem;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .checkbox-group { margin: 1rem 0; }
        .checkbox-group label { display: flex; align-items: center; cursor: pointer; }
        .checkbox-group input { margin-right: 0.5rem; width: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 LightBlog CMS</h1>
            <p>AI-Powered Auto-Blogging Platform - Installation Wizard</p>
        </div>

        <div class="content">
            <div class="progress">
                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
            </div>

            <?php
            // Step 1: Requirements Check
            if (!isset($_GET['step']) || $_GET['step'] == 1):
                // Try to create content directory if it doesn't exist
                if (!is_dir(__DIR__ . '/content')) {
                    @mkdir(__DIR__ . '/content', 0755, true);
                    @mkdir(__DIR__ . '/content/uploads', 0755, true);
                    @mkdir(__DIR__ . '/content/cache', 0755, true);
                    @mkdir(__DIR__ . '/content/database', 0755, true);
                }

                $checks = [
                    'PHP Version (>= 8.0)' => version_compare(PHP_VERSION, '8.0.0', '>='),
                    'PDO Extension' => extension_loaded('pdo'),
                    'PDO SQLite' => extension_loaded('pdo_sqlite'),
                    'GD Extension' => extension_loaded('gd'),
                    'cURL Extension' => extension_loaded('curl'),
                    'mbstring Extension' => extension_loaded('mbstring'),
                    'content/ Writable' => is_writable(__DIR__ . '/content'),
                ];

                $allPassed = !in_array(false, $checks);
            ?>
                <div class="step active">
                    <h2>Step 1: System Requirements</h2>
                    <p style="margin: 1rem 0;">Checking if your server meets the requirements...</p>

                    <?php foreach ($checks as $name => $passed): ?>
                        <div class="check-item <?= $passed ? 'success' : 'error' ?>">
                            <span class="check-icon"><?= $passed ? '✓' : '✗' ?></span>
                            <span><?= $name ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$allPassed): ?>
                        <div class="alert alert-error" style="margin-top: 1.5rem;">
                            <strong>⚠️ Permission Issues Detected</strong>
                            <p style="margin: 0.5rem 0;">Please fix the requirements above before continuing.</p>

                            <?php if (!is_writable(__DIR__ . '/content')): ?>
                                <hr style="margin: 1rem 0; opacity: 0.3;">
                                <p style="margin-bottom: 0.5rem;"><strong>How to fix "content/ Writable":</strong></p>

                                <div style="background: #fff; padding: 1rem; border-radius: 0.5rem; margin: 0.5rem 0;">
                                    <p style="margin-bottom: 0.5rem; color: #333;"><strong>Option 1 - Via FTP/File Manager:</strong></p>
                                    <ol style="margin-left: 1.5rem; color: #333;">
                                        <li>Right-click on the <code>content</code> folder</li>
                                        <li>Select "File Permissions" or "CHMOD"</li>
                                        <li>Set permissions to <strong>755</strong> or <strong>775</strong></li>
                                        <li>Check "Apply to subdirectories"</li>
                                        <li>Click Apply/OK</li>
                                    </ol>
                                </div>

                                <div style="background: #fff; padding: 1rem; border-radius: 0.5rem; margin: 0.5rem 0;">
                                    <p style="margin-bottom: 0.5rem; color: #333;"><strong>Option 2 - Via Terminal/SSH:</strong></p>
                                    <pre style="background: #1f2937; color: #10b981; padding: 0.75rem; border-radius: 0.375rem; overflow-x: auto; font-size: 0.875rem; margin: 0.5rem 0;">chmod -R 755 content/</pre>
                                    <p style="margin-top: 0.5rem; color: #666; font-size: 0.875rem;">Or if that doesn't work, try:</p>
                                    <pre style="background: #1f2937; color: #10b981; padding: 0.75rem; border-radius: 0.375rem; overflow-x: auto; font-size: 0.875rem; margin: 0.5rem 0;">chmod -R 777 content/</pre>
                                </div>

                                <div style="background: #fff; padding: 1rem; border-radius: 0.5rem; margin: 0.5rem 0;">
                                    <p style="margin-bottom: 0.5rem; color: #333;"><strong>Option 3 - Run Fix Script:</strong></p>
                                    <p style="color: #666; font-size: 0.875rem;">Download and run: <a href="/fix-permissions.php" style="color: #667eea;">fix-permissions.php</a></p>
                                </div>

                                <p style="margin-top: 1rem; font-size: 0.875rem;">
                                    <strong>After fixing permissions, refresh this page.</strong>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="btn-group">
                        <?php if ($allPassed): ?>
                            <a href="?step=2" class="btn btn-primary">Continue →</a>
                        <?php else: ?>
                            <button onclick="location.reload()" class="btn btn-secondary">🔄 Check Again</button>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($_GET['step'] == 2): ?>
                <div class="step active">
                    <h2>Step 2: Database Configuration</h2>

                    <form method="POST" action="?step=3">
                        <div class="form-group">
                            <label>Database Type</label>
                            <select name="db_type" id="dbType" onchange="toggleDbFields()">
                                <option value="sqlite">SQLite (Recommended for small sites)</option>
                                <option value="mysql">MySQL (Recommended for large sites)</option>
                            </select>
                        </div>

                        <div id="mysqlFields" style="display: none;">
                            <div class="form-group">
                                <label>MySQL Host *</label>
                                <input type="text" name="db_host" value="localhost" id="db_host">
                            </div>

                            <div class="form-group">
                                <label>Database Name *</label>
                                <input type="text" name="db_name" value="lightblog" id="db_name">
                            </div>

                            <div class="form-group">
                                <label>Database User *</label>
                                <input type="text" name="db_user" id="db_user">
                            </div>

                            <div class="form-group">
                                <label>Database Password</label>
                                <input type="password" name="db_pass" id="db_pass" placeholder="Leave empty if no password">
                                <small>Note: Leave empty only if your database has no password</small>
                            </div>
                        </div>

                        <div class="btn-group">
                            <a href="?step=1" class="btn btn-secondary">← Back</a>
                            <button type="submit" class="btn btn-primary">Continue →</button>
                        </div>
                    </form>
                </div>

                <script>
                function toggleDbFields() {
                    const dbType = document.getElementById('dbType').value;
                    const mysqlFields = document.getElementById('mysqlFields');
                    mysqlFields.style.display = dbType === 'mysql' ? 'block' : 'none';

                    // Update field requirements
                    const fields = ['db_host', 'db_name', 'db_user'];
                    fields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            field.required = (dbType === 'mysql');
                        }
                    });
                }

                // Validate form before submission
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.querySelector('form[action="?step=3"]');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            const dbType = document.getElementById('dbType').value;
                            if (dbType === 'mysql') {
                                const host = document.getElementById('db_host').value.trim();
                                const name = document.getElementById('db_name').value.trim();
                                const user = document.getElementById('db_user').value.trim();

                                if (!host || !name || !user) {
                                    e.preventDefault();
                                    alert('Please fill in all required MySQL fields (Host, Database Name, and User).');
                                    return false;
                                }
                            }
                        });
                    }
                });
                </script>

            <?php elseif ($_GET['step'] == 3 && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php
                // Save database config to session
                $_SESSION['db_type'] = $_POST['db_type'];
                $_SESSION['db_host'] = $_POST['db_host'] ?? '';
                $_SESSION['db_name'] = $_POST['db_name'] ?? '';
                $_SESSION['db_user'] = $_POST['db_user'] ?? '';
                $_SESSION['db_pass'] = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';

                // Validate MySQL credentials
                if ($_SESSION['db_type'] === 'mysql') {
                    if (empty($_SESSION['db_host']) || empty($_SESSION['db_name']) || empty($_SESSION['db_user'])) {
                        echo '<div class="step active">';
                        echo '<h2>Error: Missing Database Credentials</h2>';
                        echo '<div class="alert alert-error">';
                        echo '<p>MySQL requires all connection details:</p>';
                        echo '<ul>';
                        if (empty($_SESSION['db_host'])) echo '<li>Database Host is required</li>';
                        if (empty($_SESSION['db_name'])) echo '<li>Database Name is required</li>';
                        if (empty($_SESSION['db_user'])) echo '<li>Database User is required</li>';
                        echo '</ul>';
                        echo '</div>';
                        echo '<a href="?step=2" class="btn btn-primary">← Go Back</a>';
                        echo '</div>';
                        exit;
                    }
                }

                // Test connection
                $connectionSuccess = false;
                $error = '';

                try {
                    if ($_SESSION['db_type'] === 'sqlite') {
                        $dbPath = __DIR__ . '/content/database/lightblog.db';
                        $dbDir = dirname($dbPath);

                        if (!is_dir($dbDir)) {
                            mkdir($dbDir, 0755, true);
                        }

                        $pdo = new PDO('sqlite:' . $dbPath);
                        $connectionSuccess = true;
                    } else {
                        $dsn = "mysql:host={$_SESSION['db_host']};charset=utf8mb4";
                        $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);

                        // Create database if it doesn't exist
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$_SESSION['db_name']}");
                        $pdo->exec("USE {$_SESSION['db_name']}");

                        $connectionSuccess = true;
                    }
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
                ?>

                <div class="step active">
                    <h2>Step 3: Database Connection</h2>

                    <?php if ($connectionSuccess): ?>
                        <div class="alert alert-success">
                            ✓ Database connection successful!
                        </div>

                        <div class="btn-group">
                            <a href="?step=4" class="btn btn-primary">Continue →</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            Connection failed: <?= htmlspecialchars($error) ?>
                        </div>

                        <div class="btn-group">
                            <a href="?step=2" class="btn btn-secondary">← Try Again</a>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($_GET['step'] == 4): ?>
                <div class="step active">
                    <h2>Step 4: Admin Account</h2>

                    <form method="POST" action="?step=5">
                        <div class="form-group">
                            <label>Admin Username</label>
                            <input type="text" name="admin_username" required minlength="3">
                        </div>

                        <div class="form-group">
                            <label>Admin Email</label>
                            <input type="email" name="admin_email" required>
                        </div>

                        <div class="form-group">
                            <label>Admin Password</label>
                            <input type="password" name="admin_password" required minlength="6">
                            <small>Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="admin_password_confirm" required>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Continue →</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($_GET['step'] == 5 && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php
                // Validate passwords match
                if ($_POST['admin_password'] !== $_POST['admin_password_confirm']) {
                    echo '<div class="alert alert-error">Passwords do not match!</div>';
                    echo '<a href="?step=4" class="btn btn-secondary">← Back</a>';
                    exit;
                }

                $_SESSION['admin_username'] = $_POST['admin_username'];
                $_SESSION['admin_email'] = $_POST['admin_email'];
                $_SESSION['admin_password'] = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
                ?>

                <div class="step active">
                    <h2>Step 5: Site Settings</h2>

                    <form method="POST" action="?step=6">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="My Blog" required>
                        </div>

                        <div class="form-group">
                            <label>Site Tagline</label>
                            <input type="text" name="site_tagline" value="AI-Powered Content Hub">
                        </div>

                        <div class="form-group">
                            <label>Site URL</label>
                            <input type="url" name="site_url" value="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>" required>
                            <small>Your site's full URL (including http:// or https://)</small>
                        </div>

                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_autoblog" value="1">
                                Enable AI Auto-Blogging (requires API keys)
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">Continue →</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($_GET['step'] == 6 && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php
                // Debug: Check if session data exists
                $sessionKeys = ['db_type', 'db_host', 'db_name', 'db_user', 'admin_username', 'admin_email', 'admin_password'];
                $missingKeys = [];
                foreach ($sessionKeys as $key) {
                    if (!isset($_SESSION[$key]) || ($_SESSION[$key] === '' && $key !== 'db_pass')) {
                        $missingKeys[] = $key;
                    }
                }

                if (!empty($missingKeys)) {
                    echo '<div class="step active">';
                    echo '<h2>⚠️ Session Data Lost</h2>';
                    echo '<div class="alert alert-error">';
                    echo '<p><strong>Your session data was lost during the installation process.</strong></p>';
                    echo '<p>Missing data: ' . implode(', ', $missingKeys) . '</p>';
                    echo '<p>This usually happens when:</p>';
                    echo '<ul>';
                    echo '<li>You took too long between steps (session timeout)</li>';
                    echo '<li>Your browser has cookies disabled</li>';
                    echo '<li>Your server has restrictive session settings</li>';
                    echo '</ul>';
                    echo '<p><strong>Solution:</strong> Please start the installation process again from the beginning.</p>';
                    echo '</div>';
                    echo '<div class="btn-group">';
                    echo '<a href="?step=1" class="btn btn-primary">Start Over</a>';
                    echo '</div>';

                    // Debug info
                    echo '<details style="margin-top: 1rem; padding: 1rem; background: rgba(0,0,0,0.05); border-radius: 0.5rem;">';
                    echo '<summary style="cursor: pointer; font-weight: bold;">🔍 Debug Information</summary>';
                    echo '<pre style="margin-top: 0.5rem; font-size: 0.875rem; overflow: auto;">';
                    echo 'Session ID: ' . session_id() . "\n";
                    echo 'Session Data: ' . print_r($_SESSION, true);
                    echo '</pre>';
                    echo '</details>';
                    echo '</div>';
                    exit;
                }

                $_SESSION['site_name'] = $_POST['site_name'];
                $_SESSION['site_tagline'] = $_POST['site_tagline'];
                $_SESSION['site_url'] = rtrim($_POST['site_url'], '/');
                $_SESSION['enable_autoblog'] = isset($_POST['enable_autoblog']);
                ?>

                <div class="step active">
                    <h2>Step 6: Installation</h2>
                    <p style="margin-bottom: 2rem;">Installing LightBlog CMS...</p>

                    <?php
                    $errors = [];

                    try {
                        // Create config file
                        $configContent = "<?php\n";
                        $configContent .= "// LightBlog CMS Configuration\n";
                        $configContent .= "define('DB_TYPE', '{$_SESSION['db_type']}');\n";

                        if ($_SESSION['db_type'] === 'mysql') {
                            $configContent .= "define('DB_HOST', '{$_SESSION['db_host']}');\n";
                            $configContent .= "define('DB_NAME', '{$_SESSION['db_name']}');\n";
                            $configContent .= "define('DB_USER', '{$_SESSION['db_user']}');\n";
                            $configContent .= "define('DB_PASS', '{$_SESSION['db_pass']}');\n";
                        }

                        $configContent .= "define('SITE_URL', '{$_SESSION['site_url']}');\n";
                        $configContent .= "define('SITE_PATH', __DIR__);\n";
                        $configContent .= "define('CONTENT_PATH', __DIR__ . '/content');\n";

                        // Auto-detect BASE_PATH from SITE_URL
                        $parsedUrl = parse_url($_SESSION['site_url']);
                        $basePath = isset($parsedUrl['path']) ? rtrim($parsedUrl['path'], '/') . '/' : '/';
                        $configContent .= "define('BASE_PATH', '{$basePath}');\n";

                        $configContent .= "define('CURRENT_THEME', 'default');\n";
                        $configContent .= "define('CACHE_ENABLED', true);\n";
                        $configContent .= "define('CACHE_TTL', 3600);\n";
                        $configContent .= "define('SECURITY_KEY', '" . bin2hex(random_bytes(32)) . "');\n";
                        $configContent .= "define('AUTOBLOG_ENABLED', " . ($_SESSION['enable_autoblog'] ? 'true' : 'false') . ");\n";
                        $configContent .= "define('OPENAI_API_KEY', '');\n";
                        $configContent .= "define('CLAUDE_API_KEY', '');\n";
                        $configContent .= "define('GEMINI_API_KEY', '');\n";
                        $configContent .= "define('DEBUG_MODE', false);\n";
                        $configContent .= "error_reporting(0);\n";
                        $configContent .= "ini_set('display_errors', 0);\n";
                        $configContent .= "date_default_timezone_set('UTC');\n";
                        $configContent .= "ini_set('session.cookie_httponly', 1);\n";
                        $configContent .= "ini_set('session.use_only_cookies', 1);\n";

                        file_put_contents(__DIR__ . '/config.php', $configContent);
                        echo '<div class="check-item success"><span class="check-icon">✓</span> Config file created</div>';

                        // Load config and create database
                        require_once __DIR__ . '/config.php';

                        if ($_SESSION['db_type'] === 'sqlite') {
                            $pdo = new PDO('sqlite:' . CONTENT_PATH . '/database/lightblog.db');
                        } else {
                            // Validate MySQL credentials are present
                            if (empty($_SESSION['db_user'])) {
                                throw new Exception('Database user is missing. Please go back to Step 2 and enter your MySQL credentials.');
                            }

                            $dsn = "mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']};charset=utf8mb4";
                            $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass']);
                        }

                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        // Execute schema
                        $schema = file_get_contents(__DIR__ . '/install/schema.sql');

                        // For MySQL, we need to adjust the schema
                        if ($_SESSION['db_type'] === 'mysql') {
                            // Replace SQLite-specific syntax with MySQL syntax
                            $schema = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $schema);
                            $schema = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $schema);

                            // Handle TEXT PRIMARY KEY (settings table)
                            $schema = str_replace('key TEXT PRIMARY KEY', '`key` VARCHAR(255) PRIMARY KEY', $schema);

                            // Replace INTEGER with INT
                            $schema = str_replace('INTEGER DEFAULT', 'INT DEFAULT', $schema);
                            $schema = str_replace('INTEGER,', 'INT,', $schema);
                            $schema = str_replace('INTEGER)', 'INT)', $schema);

                            // Replace REAL with DECIMAL for precision
                            $schema = str_replace('REAL DEFAULT', 'DECIMAL(10,4) DEFAULT', $schema);
                            $schema = str_replace('REAL,', 'DECIMAL(10,4),', $schema);

                            // Replace TEXT with appropriate types for better MySQL performance
                            // (keeping TEXT for content fields, using VARCHAR for shorter fields)
                            $schema = str_replace('TEXT UNIQUE', 'VARCHAR(255) UNIQUE', $schema);
                            $schema = str_replace('TEXT DEFAULT', 'VARCHAR(100) DEFAULT', $schema);
                        }

                        // Split schema into individual statements and execute
                        $statements = array_filter(array_map('trim', explode(';', $schema)));
                        foreach ($statements as $statement) {
                            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                                try {
                                    $pdo->exec($statement);
                                } catch (PDOException $e) {
                                    // Log error but continue for non-critical statements (like indexes)
                                    if (strpos($statement, 'CREATE INDEX') === false) {
                                        throw $e;
                                    }
                                }
                            }
                        }
                        echo '<div class="check-item success"><span class="check-icon">✓</span> Database tables created</div>';

                        // Create admin user
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', ?)");
                        $stmt->execute([
                            $_SESSION['admin_username'],
                            $_SESSION['admin_email'],
                            $_SESSION['admin_password'],
                            date('Y-m-d H:i:s')
                        ]);
                        echo '<div class="check-item success"><span class="check-icon">✓</span> Admin user created</div>';

                        // Insert default settings
                        $settings = [
                            'site_name' => $_SESSION['site_name'],
                            'site_tagline' => $_SESSION['site_tagline'],
                            'posts_per_page' => '10',
                            'theme' => 'default',
                            'timezone' => 'UTC'
                        ];

                        foreach ($settings as $key => $value) {
                            $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)");
                            $stmt->execute([$key, $value]);
                        }
                        echo '<div class="check-item success"><span class="check-icon">✓</span> Default settings saved</div>';

                        // Create sample post
                        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, status, author_id, created_at, published_at, views) VALUES (?, ?, ?, ?, 'published', 1, ?, ?, 0)");
                        $stmt->execute([
                            'Welcome to LightBlog CMS!',
                            'welcome-to-lightblog-cms',
                            '<h1>Welcome to LightBlog CMS!</h1><p>Congratulations! You have successfully installed LightBlog CMS - the AI-powered auto-blogging platform.</p><h2>What\'s Next?</h2><ul><li>Go to the <a href="/admin">Admin Panel</a> to start creating content</li><li>Enable AI auto-blogging in settings</li><li>Create your first campaign</li><li>Customize your theme</li></ul><p>Happy blogging!</p>',
                            'Congratulations! You have successfully installed LightBlog CMS.',
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s')
                        ]);
                        echo '<div class="check-item success"><span class="check-icon">✓</span> Sample post created</div>';

                        echo '<div class="alert alert-success" style="margin-top: 2rem;">
                            <h3>🎉 Installation Complete!</h3>
                            <p>LightBlog CMS has been successfully installed.</p>
                        </div>';

                        // Use the calculated BASE_PATH for the admin link
                        $adminUrl = rtrim($basePath, '/') . '/admin';
                        echo '<div class="btn-group">
                            <a href="' . htmlspecialchars($adminUrl) . '" class="btn btn-primary">Go to Admin Panel →</a>
                        </div>';

                        // Delete install.php for security
                        // unlink(__FILE__); // Uncommented in production

                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
                        echo '<div class="alert alert-error">';
                        echo '<h3>❌ Installation Failed</h3>';
                        echo '<p><strong>Error:</strong> ' . htmlspecialchars($errorMsg) . '</p>';

                        // Provide helpful hints based on error type
                        if (strpos($errorMsg, 'Access denied') !== false) {
                            echo '<div style="margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 4px;">';
                            echo '<p><strong>💡 Common Solutions:</strong></p>';
                            echo '<ul style="margin: 0.5rem 0; padding-left: 1.5rem;">';
                            echo '<li>Check that your database username is correct</li>';
                            echo '<li>Verify that your database password is correct</li>';
                            echo '<li>Ensure your database user has permission to access the database</li>';
                            echo '<li>Contact your hosting provider to verify your database credentials</li>';
                            echo '</ul>';
                            echo '</div>';
                        } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'Can\'t connect') !== false) {
                            echo '<div style="margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 4px;">';
                            echo '<p><strong>💡 Common Solutions:</strong></p>';
                            echo '<ul style="margin: 0.5rem 0; padding-left: 1.5rem;">';
                            echo '<li>Check that MySQL/MariaDB is running on your server</li>';
                            echo '<li>Verify the database host (usually "localhost")</li>';
                            echo '<li>Contact your hosting provider if the issue persists</li>';
                            echo '</ul>';
                            echo '</div>';
                        }

                        echo '</div>';
                        echo '<div class="btn-group">';
                        echo '<a href="?step=2" class="btn btn-primary">← Go Back to Database Setup</a>';
                        echo '<a href="?step=1" class="btn btn-secondary">Start Over</a>';
                        echo '</div>';

                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            echo '<details style="margin-top: 1rem;"><summary>Debug Info (click to expand)</summary>';
                            echo '<pre style="font-size: 0.875rem; overflow: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                            echo '</details>';
                        }
                    }
                    ?>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Update progress bar
        const step = new URLSearchParams(window.location.search).get('step') || 1;
        const progress = (step / 6) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
    </script>
</body>
</html>
