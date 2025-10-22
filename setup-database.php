<?php
/**
 * LightBlog CMS - Database Setup Script
 * Run this file if your installation completed but tables weren't created
 */

// Check if config exists
if (!file_exists(__DIR__ . '/config.php')) {
    die('Error: config.php not found. Please run install.php first.');
}

require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - LightBlog CMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 1rem; }
        .success { padding: 1rem; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 0.5rem; margin: 1rem 0; }
        .error { padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 0.5rem; margin: 1rem 0; }
        .info { padding: 1rem; background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; border-radius: 0.5rem; margin: 1rem 0; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 0.5rem; border: none; cursor: pointer; font-size: 1rem; }
        .btn:hover { background: #5568d3; }
        pre { background: #f4f4f4; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem; }
        .check-item { padding: 0.5rem; margin: 0.5rem 0; }
        .check-icon { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Setup</h1>

        <div class="info">
            <strong>ℹ️ About this tool:</strong><br>
            This script will create all necessary database tables for LightBlog CMS.<br>
            Run this if your installation completed but you're getting "table not found" errors.
        </div>

        <?php
        if (!isset($_POST['setup_database'])) {
            // Show setup form
            ?>
            <h2>Current Configuration:</h2>
            <pre>Database Type: <?= DB_TYPE ?>
<?php if (DB_TYPE === 'mysql'): ?>
Host: <?= DB_HOST ?>

Database: <?= DB_NAME ?>

User: <?= DB_USER ?>

<?php else: ?>
Database File: <?= CONTENT_PATH ?>/database/lightblog.db
<?php endif; ?></pre>

            <form method="POST">
                <button type="submit" name="setup_database" class="btn">▶️ Setup Database Tables</button>
            </form>
            <?php
        } else {
            // Execute database setup
            echo '<h2>Setting up database...</h2>';

            try {
                // Connect to database
                if (DB_TYPE === 'sqlite') {
                    $pdo = new PDO('sqlite:' . CONTENT_PATH . '/database/lightblog.db');
                } else {
                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    $pdo = new PDO($dsn, DB_USER, DB_PASS);
                }

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo '<div class="check-item">✓ Database connection successful</div>';

                // Load schema
                $schema = file_get_contents(__DIR__ . '/install/schema.sql');

                // For MySQL, adjust schema
                if (DB_TYPE === 'mysql') {
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
                    $schema = str_replace('TEXT UNIQUE', 'VARCHAR(255) UNIQUE', $schema);
                    $schema = str_replace('TEXT DEFAULT', 'VARCHAR(100) DEFAULT', $schema);
                }

                echo '<div class="check-item">✓ Schema loaded and converted for ' . DB_TYPE . '</div>';

                // Split schema into individual statements and execute
                $statements = array_filter(array_map('trim', explode(';', $schema)));
                $successCount = 0;
                $skipCount = 0;
                $errors = [];

                foreach ($statements as $statement) {
                    if (empty($statement) || preg_match('/^--/', $statement)) {
                        continue;
                    }

                    try {
                        $pdo->exec($statement);
                        $successCount++;

                        // Show which table was created
                        if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches)) {
                            echo '<div class="check-item">✓ Created table: ' . htmlspecialchars($matches[1]) . '</div>';
                        } elseif (preg_match('/CREATE INDEX.*?(\w+)/i', $statement, $matches)) {
                            echo '<div class="check-item">✓ Created index: ' . htmlspecialchars($matches[1]) . '</div>';
                        }

                    } catch (PDOException $e) {
                        // Check if table already exists
                        if (strpos($e->getMessage(), 'already exists') !== false) {
                            $skipCount++;
                            if (preg_match('/table\s+`?(\w+)`?/i', $e->getMessage(), $matches)) {
                                echo '<div class="check-item" style="color: #856404;">⚠ Table already exists: ' . htmlspecialchars($matches[1]) . '</div>';
                            }
                        } else {
                            // Real error - show the SQL statement
                            $errors[] = $e->getMessage();
                            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            echo '<details style="margin: 0.5rem 0; padding: 0.5rem; background: #f8f9fa; border-radius: 0.25rem;">';
                            echo '<summary style="cursor: pointer;">View SQL Statement</summary>';
                            echo '<pre style="margin-top: 0.5rem; font-size: 0.75rem; overflow-x: auto;">' . htmlspecialchars($statement) . '</pre>';
                            echo '</details>';
                        }
                    }
                }

                // Summary
                echo '<div class="success">';
                echo '<h3>✅ Database Setup Complete!</h3>';
                echo '<p>Successfully created: ' . $successCount . ' tables/indexes</p>';
                if ($skipCount > 0) {
                    echo '<p>Already existed: ' . $skipCount . ' tables</p>';
                }
                if (!empty($errors)) {
                    echo '<p>Errors: ' . count($errors) . '</p>';
                }
                echo '</div>';

                // Check if tables exist now
                echo '<h3>Verifying Tables:</h3>';
                $coreTables = ['posts', 'users', 'settings', 'campaigns', 'ai_queue'];
                $allExist = true;

                foreach ($coreTables as $table) {
                    try {
                        $result = $pdo->query("SELECT COUNT(*) FROM $table");
                        $count = $result->fetchColumn();
                        echo '<div class="check-item">✓ Table <strong>' . $table . '</strong> exists (' . $count . ' rows)</div>';
                    } catch (PDOException $e) {
                        echo '<div class="error">❌ Table <strong>' . $table . '</strong> does NOT exist</div>';
                        $allExist = false;
                    }
                }

                if ($allExist) {
                    $basePath = defined('BASE_PATH') ? BASE_PATH : '/';
                    echo '<div class="success">';
                    echo '<p>✅ All core tables verified successfully!</p>';
                    echo '<p><a href="' . htmlspecialchars($basePath) . 'admin/login.php" class="btn">Go to Admin Panel →</a></p>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<p>⚠️ Some tables are still missing. There may be a database permission issue.</p>';
                    echo '<p>Please contact your hosting provider to ensure your database user has CREATE TABLE permissions.</p>';
                    echo '</div>';
                }

            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h3>❌ Setup Failed</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
        }
        ?>
    </div>
</body>
</html>
