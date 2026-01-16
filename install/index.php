<?php
/**
 * LightBlog CMS - Web Installer
 * Automatic installation wizard for fresh installations
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('SITE_PATH', dirname(__DIR__));

// Step tracking
$step = $_GET['step'] ?? 'welcome';
$errors = [];
$success = [];

// Function to test database connection
function testDatabaseConnection($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]);

        // Try to create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to execute SQL file
function executeSQLFile($pdo, $filepath) {
    $sql = file_get_contents($filepath);

    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split by semicolon but handle strings properly
    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $escaped = false;

    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];

        // Handle escape sequences
        if ($escaped) {
            $buffer .= $char;
            $escaped = false;
            continue;
        }

        if ($char === '\\') {
            $buffer .= $char;
            $escaped = true;
            continue;
        }

        // Handle strings
        if (($char === '"' || $char === "'") && !$inString) {
            $inString = true;
            $stringChar = $char;
            $buffer .= $char;
            continue;
        }

        if ($char === $stringChar && $inString) {
            $inString = false;
            $stringChar = '';
            $buffer .= $char;
            continue;
        }

        // Handle semicolons
        if ($char === ';' && !$inString) {
            $stmt = trim($buffer);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    // Add last statement if any
    $stmt = trim($buffer);
    if (!empty($stmt)) {
        $statements[] = $stmt;
    }

    $executed = 0;
    $failed = 0;
    $errors = [];

    foreach ($statements as $index => $statement) {
        // Skip empty statements
        if (empty($statement)) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            $failed++;
            // Get first 100 chars of statement for error reporting
            $stmtPreview = substr($statement, 0, 100);
            $errors[] = "Statement #" . ($index + 1) . ": " . $e->getMessage() . " (SQL: " . $stmtPreview . "...)";
        }
    }

    return [
        'success' => $failed === 0,
        'executed' => $executed,
        'failed' => $failed,
        'errors' => $errors
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 'database') {
        // Step 2: Database Setup
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $siteUrl = trim($_POST['site_url'] ?? '');

        // Validate inputs
        if (empty($dbName) || empty($dbUser) || empty($siteUrl)) {
            $errors[] = "All fields except password are required.";
        } else {
            // Test connection
            $testResult = testDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);

            if ($testResult['success']) {
                // Store in session
                $_SESSION['install_data'] = [
                    'db_host' => $dbHost,
                    'db_name' => $dbName,
                    'db_user' => $dbUser,
                    'db_pass' => $dbPass,
                    'site_url' => rtrim($siteUrl, '/')
                ];

                // Execute schema.sql
                $schemaResult = executeSQLFile($testResult['pdo'], __DIR__ . '/schema.sql');

                if ($schemaResult['success']) {
                    $_SESSION['install_data']['schema_executed'] = true;
                    header('Location: index.php?step=admin');
                    exit;
                } else {
                    $errors[] = "Failed to create database tables. Errors: " . implode(', ', $schemaResult['errors']);
                }
            } else {
                $errors[] = "Database connection failed: " . $testResult['error'];
            }
        }
    }

    elseif ($step === 'admin') {
        // Step 3: Create Admin User
        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminEmail = trim($_POST['admin_email'] ?? '');

        if (empty($adminUser) || empty($adminPass) || empty($adminEmail)) {
            $errors[] = "All fields are required.";
        } elseif (strlen($adminPass) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address.";
        } else {
            // Create admin user
            $installData = $_SESSION['install_data'] ?? [];

            if (!empty($installData)) {
                try {
                    $dsn = "mysql:host={$installData['db_host']};dbname={$installData['db_name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $installData['db_user'], $installData['db_pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    // Check if admin user already exists
                    $hashedPassword = password_hash($adminPass, PASSWORD_BCRYPT);
                    $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $checkUser->execute([$adminUser]);

                    if ($checkUser->fetch()) {
                        // Update existing admin user
                        $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, role = 'admin' WHERE username = ?");
                        $stmt->execute([$adminEmail, $hashedPassword, $adminUser]);
                    } else {
                        // Insert new admin user
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
                        $stmt->execute([$adminUser, $adminEmail, $hashedPassword]);
                    }

                    // Insert default category if not exists
                    $checkCat = $pdo->query("SELECT COUNT(*) FROM categories WHERE slug = 'uncategorized'")->fetchColumn();
                    if ($checkCat == 0) {
                        $pdo->exec("INSERT INTO categories (name, slug, description) VALUES ('Uncategorized', 'uncategorized', 'Default category')");
                    }

                    // Store admin info
                    $_SESSION['install_data']['admin_created'] = true;

                    header('Location: index.php?step=config');
                    exit;

                } catch (PDOException $e) {
                    $errors[] = "Failed to create admin user: " . $e->getMessage();
                }
            } else {
                $errors[] = "Installation data lost. Please start over.";
            }
        }
    }

    elseif ($step === 'config') {
        // Step 4: Generate config.php
        $installData = $_SESSION['install_data'] ?? [];

        if (!empty($installData)) {
            // Optional API keys
            $openaiKey = trim($_POST['openai_key'] ?? '');
            $claudeKey = trim($_POST['claude_key'] ?? '');
            $geminiKey = trim($_POST['gemini_key'] ?? '');
            $unsplashKey = trim($_POST['unsplash_key'] ?? '');

            // Generate config.php
            $configContent = "<?php\n";
            $configContent .= "/**\n";
            $configContent .= " * LightBlog CMS - Configuration File\n";
            $configContent .= " * Generated by installer on " . date('Y-m-d H:i:s') . "\n";
            $configContent .= " */\n\n";

            $configContent .= "// Database Configuration\n";
            $configContent .= "define('DB_TYPE', 'mysql'); // mysql or sqlite\n";
            $configContent .= "define('DB_HOST', '" . addslashes($installData['db_host']) . "');\n";
            $configContent .= "define('DB_NAME', '" . addslashes($installData['db_name']) . "');\n";
            $configContent .= "define('DB_USER', '" . addslashes($installData['db_user']) . "');\n";
            $configContent .= "define('DB_PASS', '" . addslashes($installData['db_pass']) . "');\n\n";

            // Calculate BASE_PATH from SITE_URL
            $parsedUrl = parse_url($installData['site_url']);
            $basePath = isset($parsedUrl['path']) ? rtrim($parsedUrl['path'], '/') . '/' : '/';

            $configContent .= "// Site Configuration\n";
            $configContent .= "define('SITE_URL', '" . addslashes($installData['site_url']) . "');\n";
            $configContent .= "define('SITE_PATH', dirname(__FILE__));\n";
            $configContent .= "define('BASE_PATH', '" . addslashes($basePath) . "');\n";
            $configContent .= "define('CURRENT_THEME', 'default'); // Current active theme\n";
            $configContent .= "define('CONTENT_PATH', SITE_PATH . '/content'); // Content directory for uploads\n\n";

            $configContent .= "// AI Provider API Keys\n";
            $configContent .= "define('OPENAI_API_KEY', '" . addslashes($openaiKey) . "');\n";
            $configContent .= "define('CLAUDE_API_KEY', '" . addslashes($claudeKey) . "');\n";
            $configContent .= "define('GEMINI_API_KEY', '" . addslashes($geminiKey) . "');\n";
            $configContent .= "define('UNSPLASH_API_KEY', '" . addslashes($unsplashKey) . "');\n\n";

            $configContent .= "// Default AI Provider Settings\n";
            $configContent .= "define('DEFAULT_TEXT_PROVIDER', 'auto'); // auto, openai, claude, gemini\n";
            $configContent .= "define('DEFAULT_IMAGE_PROVIDER', 'auto'); // auto, openai, unsplash, gd\n";
            $configContent .= "define('DEFAULT_TEXT_MODEL', 'gpt-4o-mini'); // OpenAI model\n\n";

            $configContent .= "// Session Configuration\n";
            $configContent .= "define('SESSION_LIFETIME', 86400); // 24 hours\n\n";

            $configContent .= "// Autoload Core Classes\n";
            $configContent .= "spl_autoload_register(function(\$class) {\n";
            $configContent .= "    \$file = SITE_PATH . '/core/' . str_replace('\\\\', '/', \$class) . '.php';\n";
            $configContent .= "    if (file_exists(\$file)) {\n";
            $configContent .= "        require_once \$file;\n";
            $configContent .= "    }\n";
            $configContent .= "});\n";

            // Write config.php
            $configPath = SITE_PATH . '/config.php';
            if (file_put_contents($configPath, $configContent)) {
                // Clear session
                $_SESSION['install_data']['config_created'] = true;
                header('Location: index.php?step=complete');
                exit;
            } else {
                $errors[] = "Failed to write config.php. Please check file permissions.";
            }
        } else {
            $errors[] = "Installation data lost. Please start over.";
        }
    }
}

// Get install data for display
$installData = $_SESSION['install_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LightBlog CMS - Installation Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .installer-container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        .installer-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .installer-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            padding: 2rem;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 0 1rem;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 15px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 0;
        }
        .step:last-child::after {
            display: none;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step.completed::after {
            background: #28a745;
        }
        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }
        .installer-content {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list li::before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        .text-center {
            text-align: center;
        }
        .mt-2 { margin-top: 1rem; }
        .info-box {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .info-box h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🚀 LightBlog CMS</h1>
            <p>Installation Wizard v1.0</p>
        </div>

        <?php if ($step !== 'welcome'): ?>
        <div class="progress-steps">
            <div class="step <?= $step === 'database' ? 'active' : ($installData['schema_executed'] ?? false ? 'completed' : '') ?>">
                <div class="step-number">1</div>
                <div class="step-label">Database</div>
            </div>
            <div class="step <?= $step === 'admin' ? 'active' : ($installData['admin_created'] ?? false ? 'completed' : '') ?>">
                <div class="step-number">2</div>
                <div class="step-label">Admin User</div>
            </div>
            <div class="step <?= $step === 'config' ? 'active' : ($installData['config_created'] ?? false ? 'completed' : '') ?>">
                <div class="step-number">3</div>
                <div class="step-label">Configuration</div>
            </div>
            <div class="step <?= $step === 'complete' ? 'active completed' : '' ?>">
                <div class="step-number">4</div>
                <div class="step-label">Complete</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="installer-content">
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($step === 'welcome'): ?>
                <!-- Welcome Screen -->
                <h2 style="margin-bottom: 1.5rem;">Welcome to LightBlog CMS!</h2>
                <p style="margin-bottom: 2rem; color: #6c757d; line-height: 1.6;">
                    Thank you for choosing LightBlog CMS. This wizard will guide you through the installation process.
                    It will take only a few minutes to set up your AI-powered blogging platform.
                </p>

                <div class="info-box">
                    <h3>Features Included:</h3>
                    <ul class="feature-list">
                        <li>AI-Powered Content Generation with OpenAI, Claude, and Gemini</li>
                        <li>Automatic SEO Optimization</li>
                        <li>Multi-Provider Image Generation (DALL-E, Unsplash)</li>
                        <li>Advanced Pages & Menu System</li>
                        <li>Affiliate Product Integration</li>
                        <li>Campaign Management & Automated Publishing</li>
                        <li>Backup & Restore Functionality</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <strong>Before you begin:</strong><br>
                    Make sure you have your database credentials ready (MySQL/MariaDB)
                </div>

                <div class="text-center">
                    <a href="index.php?step=database" class="btn btn-primary">Let's Get Started →</a>
                </div>

            <?php elseif ($step === 'database'): ?>
                <!-- Step 1: Database Configuration -->
                <h2 style="margin-bottom: 1.5rem;">Database Configuration</h2>
                <p style="margin-bottom: 2rem; color: #6c757d;">
                    Enter your MySQL/MariaDB database credentials. The installer will create the database if it doesn't exist.
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                        <small>Usually "localhost"</small>
                    </div>

                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" class="form-control" placeholder="lightblog" required>
                        <small>Will be created if it doesn't exist</small>
                    </div>

                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass" class="form-control">
                        <small>Leave empty if no password</small>
                    </div>

                    <div class="form-group">
                        <label>Site URL</label>
                        <input type="url" name="site_url" class="form-control"
                               value="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF'])) ?>" required>
                        <small>Your website URL (without trailing slash)</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Create Database & Tables →</button>
                    </div>
                </form>

            <?php elseif ($step === 'admin'): ?>
                <!-- Step 2: Admin User Creation -->
                <h2 style="margin-bottom: 1.5rem;">Create Admin Account</h2>
                <p style="margin-bottom: 2rem; color: #6c757d;">
                    Create your administrator account to access the admin panel.
                </p>

                <div class="alert alert-success">
                    ✓ Database created successfully with all tables and columns!
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="admin_user" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Admin Password</label>
                        <input type="password" name="admin_pass" class="form-control" required>
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Create Admin Account →</button>
                    </div>
                </form>

            <?php elseif ($step === 'config'): ?>
                <!-- Step 3: Configuration & API Keys -->
                <h2 style="margin-bottom: 1.5rem;">AI Provider Configuration</h2>
                <p style="margin-bottom: 2rem; color: #6c757d;">
                    Configure your AI provider API keys (optional). You can add or change these later in admin settings.
                </p>

                <div class="alert alert-success">
                    ✓ Admin account created successfully!
                </div>

                <form method="POST">
                    <div class="info-box">
                        <h3>Text Generation Providers</h3>

                        <div class="form-group">
                            <label>OpenAI API Key (Optional)</label>
                            <input type="text" name="openai_key" class="form-control" placeholder="sk-...">
                            <small>For GPT-4 and DALL-E image generation</small>
                        </div>

                        <div class="form-group">
                            <label>Claude API Key (Optional)</label>
                            <input type="text" name="claude_key" class="form-control" placeholder="sk-ant-...">
                            <small>For Anthropic Claude models</small>
                        </div>

                        <div class="form-group">
                            <label>Google Gemini API Key (Optional)</label>
                            <input type="text" name="gemini_key" class="form-control">
                            <small>For Google Gemini models (Free tier available)</small>
                        </div>
                    </div>

                    <div class="info-box">
                        <h3>Image Generation Provider</h3>

                        <div class="form-group">
                            <label>Unsplash API Key (Optional)</label>
                            <input type="text" name="unsplash_key" class="form-control">
                            <small>For FREE stock photos (500 requests/hour)</small>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> All API keys are optional. You can skip this step and add them later in Admin Settings.
                        LightBlog will use PHP GD for basic image generation as a fallback.
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Generate Configuration File →</button>
                    </div>
                </form>

            <?php elseif ($step === 'complete'): ?>
                <!-- Step 4: Installation Complete -->
                <div class="text-center">
                    <div class="success-icon">🎉</div>
                    <h2 style="margin-bottom: 1rem;">Installation Complete!</h2>
                    <p style="color: #6c757d; margin-bottom: 2rem;">
                        LightBlog CMS has been successfully installed with complete database structure.
                    </p>

                    <div class="alert alert-success">
                        <strong>All database tables created with full columns including:</strong><br>
                        ✓ posts (65 columns with all SEO fields)<br>
                        ✓ categories, tags, users<br>
                        ✓ pages, menus, menu_items<br>
                        ✓ campaigns, ai_queue<br>
                        ✓ affiliate_products, media<br>
                        <br>
                        <strong>No migration needed - everything is ready!</strong>
                    </div>

                    <div class="info-box">
                        <h3>Next Steps:</h3>
                        <ul class="feature-list">
                            <li>Delete or rename the /install directory for security</li>
                            <li>Log in to your admin panel</li>
                            <li>Configure your site settings</li>
                            <li>Start creating amazing content!</li>
                        </ul>
                    </div>

                    <a href="../admin/login.php" class="btn btn-success" style="margin-right: 1rem;">Go to Admin Panel</a>
                    <a href="../index.php" class="btn btn-primary">View Your Site</a>

                    <div class="alert alert-info mt-2">
                        <strong>Database Structure:</strong> Your database has been created with the complete schema
                        including all SEO columns (focus_keyword, og_title, twitter_title, schema_type, readability_score,
                        seo_score, etc.). No manual SQL import or migration tools needed!
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
// Clean up session after completion
if ($step === 'complete' && isset($_GET['cleanup'])) {
    unset($_SESSION['install_data']);
}
?>
