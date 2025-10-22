<?php
/**
 * Fix posts and campaigns tables
 */

require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Posts & Campaigns Tables - LightBlog</title>
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
        .warning { padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; border-radius: 0.5rem; margin: 1rem 0; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #667eea; color: white; text-decoration: none; border-radius: 0.5rem; border: none; cursor: pointer; font-size: 1rem; margin: 0.5rem; }
        .btn:hover { background: #5568d3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre { background: #f4f4f4; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Posts & Campaigns Tables</h1>

        <?php
        if (!isset($_POST['action'])) {
            ?>
            <div class="warning">
                <h3>⚠️ Warning</h3>
                <p>This will <strong>DROP and RECREATE</strong> the posts and campaigns tables.</p>
                <p>Any existing data in these tables will be lost.</p>
            </div>

            <form method="POST">
                <button type="submit" name="action" value="fix" class="btn btn-danger">
                    ⚠️ Drop & Recreate Tables
                </button>
                <a href="<?= BASE_PATH ?>admin/login.php" class="btn">Cancel</a>
            </form>
            <?php
        } else {
            echo '<h2>Fixing tables...</h2>';

            try {
                // Connect
                if (DB_TYPE === 'sqlite') {
                    $pdo = new PDO('sqlite:' . CONTENT_PATH . '/database/lightblog.db');
                } else {
                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                    $pdo = new PDO($dsn, DB_USER, DB_PASS);
                }
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                echo '<div>✓ Connected to database</div>';

                // Drop existing tables if they exist
                echo '<h3>Step 1: Dropping existing tables...</h3>';

                try {
                    $pdo->exec("DROP TABLE IF EXISTS posts");
                    echo '<div>✓ Dropped table: posts</div>';
                } catch (PDOException $e) {
                    echo '<div class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }

                try {
                    $pdo->exec("DROP TABLE IF EXISTS campaigns");
                    echo '<div>✓ Dropped table: campaigns</div>';
                } catch (PDOException $e) {
                    echo '<div class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }

                // Also drop related tables that might have foreign key constraints
                try {
                    $pdo->exec("DROP TABLE IF EXISTS affiliate_networks");
                    echo '<div>✓ Dropped table: affiliate_networks</div>';
                } catch (PDOException $e) {
                    echo '<div class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }

                try {
                    $pdo->exec("DROP TABLE IF EXISTS satellite_sites");
                    echo '<div>✓ Dropped table: satellite_sites</div>';
                } catch (PDOException $e) {
                    echo '<div class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }

                try {
                    $pdo->exec("DROP TABLE IF EXISTS cron_logs");
                    echo '<div>✓ Dropped table: cron_logs</div>';
                } catch (PDOException $e) {
                    echo '<div class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }

                try {
                    $pdo->exec("DROP TABLE IF EXISTS media");
                    echo '<div>✓ Dropped table: media</div>';
                } catch (PDOException $e) {
                    echo '<div class="error">Warning: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }

                // Create posts table
                echo '<h3>Step 2: Creating posts table...</h3>';
                $postsSql = "CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(500),
    author_id INT,
    status VARCHAR(100) DEFAULT 'draft',
    is_ai_generated INT DEFAULT 0,
    campaign_id INT,
    site_id INT DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    published_at DATETIME,
    views INT DEFAULT 0,
    seo_title VARCHAR(500),
    meta_description TEXT,
    keywords TEXT
)";
                $pdo->exec($postsSql);
                echo '<div class="success">✅ Created table: posts</div>';

                // Create campaigns table
                echo '<h3>Step 3: Creating campaigns table...</h3>';
                $campaignsSql = "CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(500) NOT NULL,
    niche VARCHAR(255),
    status VARCHAR(100) DEFAULT 'active',
    goal VARCHAR(255),
    seed_keywords TEXT,
    target_count INT,
    frequency VARCHAR(100),
    posts_per_day INT,
    content_types TEXT,
    word_count_min INT DEFAULT 1500,
    word_count_max INT DEFAULT 2500,
    keyword_density DECIMAL(10,4) DEFAULT 1.5,
    auto_internal_links INT DEFAULT 3,
    ai_provider VARCHAR(100),
    ai_model VARCHAR(255),
    ai_temperature DECIMAL(10,4) DEFAULT 0.7,
    tone VARCHAR(100),
    language VARCHAR(100) DEFAULT 'en',
    affiliate_settings TEXT,
    start_date DATE,
    end_date DATE,
    publish_times TEXT,
    timezone VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME
)";
                $pdo->exec($campaignsSql);
                echo '<div class="success">✅ Created table: campaigns</div>';

                // Create other missing tables
                echo '<h3>Step 4: Creating other missing tables...</h3>';

                // affiliate_networks
                $pdo->exec("CREATE TABLE affiliate_networks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(500) NOT NULL,
    api_key VARCHAR(500),
    tracking_id VARCHAR(255),
    commission_rate DECIMAL(10,4),
    cookie_duration INT,
    status VARCHAR(100) DEFAULT 'active'
)");
                echo '<div>✓ Created table: affiliate_networks</div>';

                // satellite_sites
                $pdo->exec("CREATE TABLE satellite_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) UNIQUE NOT NULL,
    api_key VARCHAR(500),
    niche VARCHAR(255),
    language VARCHAR(100) DEFAULT 'en',
    status VARCHAR(100) DEFAULT 'active',
    posts_count INT DEFAULT 0,
    main_site_id INT,
    created_at DATETIME
)");
                echo '<div>✓ Created table: satellite_sites</div>';

                // cron_logs
                $pdo->exec("CREATE TABLE cron_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255),
    status VARCHAR(100),
    message TEXT,
    executed_at DATETIME
)");
                echo '<div>✓ Created table: cron_logs</div>';

                // media
                $pdo->exec("CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(500) NOT NULL,
    original_filename VARCHAR(500),
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    width INT,
    height INT,
    uploaded_by INT,
    uploaded_at DATETIME
)");
                echo '<div>✓ Created table: media</div>';

                // Create indexes
                echo '<h3>Step 5: Creating indexes...</h3>';
                $pdo->exec("CREATE INDEX idx_posts_status ON posts(status)");
                echo '<div>✓ Created index: idx_posts_status</div>';

                $pdo->exec("CREATE INDEX idx_posts_slug ON posts(slug)");
                echo '<div>✓ Created index: idx_posts_slug</div>';

                $pdo->exec("CREATE INDEX idx_posts_campaign ON posts(campaign_id)");
                echo '<div>✓ Created index: idx_posts_campaign</div>';

                $pdo->exec("CREATE INDEX idx_posts_published ON posts(published_at)");
                echo '<div>✓ Created index: idx_posts_published</div>';

                $pdo->exec("CREATE INDEX idx_campaigns_status ON campaigns(status)");
                echo '<div>✓ Created index: idx_campaigns_status</div>';

                // Verify
                echo '<h3>Verification:</h3>';
                $result = $pdo->query("SELECT COUNT(*) FROM posts");
                echo '<div class="success">✅ posts table is working! (' . $result->fetchColumn() . ' rows)</div>';

                $result = $pdo->query("SELECT COUNT(*) FROM campaigns");
                echo '<div class="success">✅ campaigns table is working! (' . $result->fetchColumn() . ' rows)</div>';

                echo '<div class="success">';
                echo '<h3>🎉 Tables Fixed Successfully!</h3>';
                echo '<p>All missing tables have been created.</p>';
                echo '<a href="' . BASE_PATH . 'admin/login.php" class="btn">Go to Admin Panel →</a>';
                echo '</div>';

            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h3>❌ Error</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div>';
            }
        }
        ?>
    </div>
</body>
</html>
