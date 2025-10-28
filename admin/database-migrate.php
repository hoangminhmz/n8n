<?php
/**
 * Database Migration Tool
 * Safely add missing columns and tables to existing database
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';

$db = Database::getInstance();
$messages = [];

// Execute migration
if (isset($_POST['run_migration'])) {
    $executed = 0;
    $errors = 0;

    // Step 1: Add missing columns to posts table
    $postsColumns = [
        ['name' => 'focus_keyword', 'type' => 'VARCHAR(255)'],
        ['name' => 'canonical_url', 'type' => 'VARCHAR(500)'],
        ['name' => 'meta_robots', 'type' => "VARCHAR(50) DEFAULT 'index,follow'"],
        ['name' => 'og_title', 'type' => 'VARCHAR(255)'],
        ['name' => 'og_description', 'type' => 'TEXT'],
        ['name' => 'og_image', 'type' => 'VARCHAR(500)'],
        ['name' => 'twitter_title', 'type' => 'VARCHAR(255)'],
        ['name' => 'twitter_description', 'type' => 'TEXT'],
        ['name' => 'twitter_image', 'type' => 'VARCHAR(500)'],
        ['name' => 'schema_type', 'type' => "VARCHAR(50) DEFAULT 'Article'"],
        ['name' => 'faq_data', 'type' => 'TEXT'],
        ['name' => 'readability_score', 'type' => 'DECIMAL(5,2)'],
        ['name' => 'word_count', 'type' => 'INT(11)'],
        ['name' => 'reading_time', 'type' => 'INT(11)'],
        ['name' => 'internal_links_count', 'type' => 'INT(11) DEFAULT 0'],
        ['name' => 'external_links_count', 'type' => 'INT(11) DEFAULT 0'],
        ['name' => 'images_count', 'type' => 'INT(11) DEFAULT 0'],
        ['name' => 'has_table_of_contents', 'type' => 'TINYINT(1) DEFAULT 0'],
        ['name' => 'seo_score', 'type' => 'INT(11) DEFAULT 0'],
        ['name' => 'last_seo_check', 'type' => 'DATETIME']
    ];

    foreach ($postsColumns as $column) {
        try {
            $sql = "ALTER TABLE `posts` ADD COLUMN `{$column['name']}` {$column['type']}";
            $db->query($sql);
            $messages[] = ['type' => 'success', 'text' => "Added column: posts.{$column['name']}"];
            $executed++;
        } catch (Exception $e) {
            // Column might already exist
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $messages[] = ['type' => 'info', 'text' => "Column already exists: posts.{$column['name']}"];
            } else {
                $messages[] = ['type' => 'error', 'text' => "Error adding posts.{$column['name']}: " . $e->getMessage()];
                $errors++;
            }
        }
    }

    // Step 2: Add missing columns to categories
    try {
        $db->query("ALTER TABLE `categories` ADD COLUMN `icon` VARCHAR(50)");
        $executed++;
    } catch (Exception $e) {
        // Already exists
    }

    try {
        $db->query("ALTER TABLE `categories` ADD COLUMN `display_in_menu` TINYINT(1) DEFAULT 1");
        $executed++;
    } catch (Exception $e) {
        // Already exists
    }

    // Step 3: Create pages table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `pages` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            `content` LONGTEXT,
            `excerpt` TEXT,
            `parent_id` INT(11) DEFAULT 0,
            `menu_order` INT(11) DEFAULT 0,
            `template` VARCHAR(50) DEFAULT 'default',
            `custom_css` TEXT,
            `custom_js` TEXT,
            `content_mode` VARCHAR(20) DEFAULT 'html',
            `ai_prompt` TEXT,
            `ai_generated` TINYINT(1) DEFAULT 0,
            `ai_provider` VARCHAR(50),
            `ai_model` VARCHAR(50),
            `template_style` VARCHAR(50),
            `last_generated_at` DATETIME,
            `generation_count` INT(11) DEFAULT 0,
            `author_id` INT(11),
            `status` VARCHAR(20) DEFAULT 'draft',
            `visibility` VARCHAR(20) DEFAULT 'public',
            `password` VARCHAR(255),
            `seo_title` VARCHAR(255),
            `meta_description` TEXT,
            `canonical_url` VARCHAR(500),
            `meta_robots` VARCHAR(50) DEFAULT 'index,follow',
            `focus_keyword` VARCHAR(255),
            `og_title` VARCHAR(255),
            `og_description` TEXT,
            `og_image` VARCHAR(500),
            `twitter_title` VARCHAR(255),
            `twitter_description` TEXT,
            `twitter_image` VARCHAR(500),
            `schema_type` VARCHAR(50) DEFAULT 'WebPage',
            `created_at` DATETIME,
            `updated_at` DATETIME,
            `published_at` DATETIME,
            `views` INT(11) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->query($sql);
        $messages[] = ['type' => 'success', 'text' => "Created table: pages"];
        $executed++;

        // Insert sample pages
        $db->query("INSERT INTO `pages` (`title`, `slug`, `content`, `status`, `published_at`, `created_at`) VALUES
            ('About Us', 'about', '<h1>About Us</h1><p>Welcome to our blog!</p>', 'published', NOW(), NOW()),
            ('Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with us.</p>', 'published', NOW(), NOW())
        ");
        $messages[] = ['type' => 'success', 'text' => "Added sample pages"];

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $messages[] = ['type' => 'error', 'text' => "Error creating pages table: " . $e->getMessage()];
            $errors++;
        }
    }

    // Step 4: Create menus table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `menus` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `location` VARCHAR(50),
            `description` TEXT,
            `created_at` DATETIME,
            `updated_at` DATETIME,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->query($sql);
        $messages[] = ['type' => 'success', 'text' => "Created table: menus"];
        $executed++;

        // Insert default menus
        $db->query("INSERT INTO `menus` (`name`, `location`, `description`, `created_at`) VALUES
            ('Primary Menu', 'primary', 'Main navigation menu', NOW()),
            ('Footer Menu', 'footer', 'Footer navigation menu', NOW())
        ");
        $messages[] = ['type' => 'success', 'text' => "Added default menus"];

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $messages[] = ['type' => 'error', 'text' => "Error creating menus table: " . $e->getMessage()];
            $errors++;
        }
    }

    // Step 5: Create menu_items table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `menu_items` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `menu_id` INT(11) UNSIGNED NOT NULL,
            `type` VARCHAR(20) NOT NULL,
            `object_id` INT(11),
            `custom_url` VARCHAR(500),
            `title` VARCHAR(255) NOT NULL,
            `css_classes` VARCHAR(255),
            `target` VARCHAR(20) DEFAULT '_self',
            `parent_id` INT(11) DEFAULT 0,
            `menu_order` INT(11) DEFAULT 0,
            `created_at` DATETIME,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->query($sql);
        $messages[] = ['type' => 'success', 'text' => "Created table: menu_items"];
        $executed++;

        // Insert default menu items
        $primaryMenuId = $db->queryOne("SELECT id FROM menus WHERE location = 'primary' LIMIT 1");
        if ($primaryMenuId) {
            $db->query("INSERT INTO `menu_items` (`menu_id`, `type`, `custom_url`, `title`, `menu_order`, `created_at`) VALUES
                ({$primaryMenuId->id}, 'custom', '/', 'Home', 0, NOW())
            ");
            $messages[] = ['type' => 'success', 'text' => "Added default menu items"];
        }

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $messages[] = ['type' => 'error', 'text' => "Error creating menu_items table: " . $e->getMessage()];
            $errors++;
        }
    }

    $messages[] = ['type' => 'success', 'text' => "Migration completed! Executed: {$executed}, Errors: {$errors}"];

    // Redirect to check page
    echo '<script>setTimeout(function(){ window.location.href = "database-check.php"; }, 2000);</script>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - LightBlog CMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .content {
            padding: 2rem;
        }
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid;
            font-size: 0.9rem;
        }
        .message.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .message.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 1rem;
        }
        .warning-box ul {
            margin-left: 1.5rem;
            color: #856404;
        }
        .warning-box li {
            margin: 0.5rem 0;
        }
        button {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 1rem 3rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
            width: 100%;
            margin-top: 1rem;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(240, 147, 251, 0.6);
        }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #f5576c;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .log-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Database Migration Tool</h1>
            <p>Add missing columns and tables safely</p>
        </div>

        <div class="content">
            <?php if (empty($messages)): ?>
                <div class="warning-box">
                    <h3>⚠️ Before You Start</h3>
                    <p>This tool will:</p>
                    <ul>
                        <li>Add missing columns to <code>posts</code> table (focus_keyword, og_title, etc.)</li>
                        <li>Add missing columns to <code>categories</code> table</li>
                        <li>Create <code>pages</code> table if it doesn't exist</li>
                        <li>Create <code>menus</code> and <code>menu_items</code> tables</li>
                        <li>Insert sample pages and default menus</li>
                    </ul>
                    <p style="margin-top: 1rem;">
                        <strong>Safe to run:</strong> Your existing data will NOT be affected. This only ADDS missing structure.
                    </p>
                </div>

                <form method="POST">
                    <button type="submit" name="run_migration">
                        🚀 Run Migration Now
                    </button>
                </form>

            <?php else: ?>
                <h3 style="margin-bottom: 1rem; color: #333;">Migration Results:</h3>
                <div class="log-box">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message['type'] ?>">
                            <?= htmlspecialchars($message['text']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p style="margin-top: 1.5rem; text-align: center; color: #666;">
                    Redirecting to database check in 2 seconds...
                </p>
            <?php endif; ?>

            <a href="database-check.php" class="back-link">← Back to Database Check</a>
        </div>
    </div>
</body>
</html>
