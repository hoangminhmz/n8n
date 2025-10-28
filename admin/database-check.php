<?php
/**
 * Database Structure Checker and Fixer
 * Check if database has all required columns and fix if needed
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';

$db = Database::getInstance();
$messages = [];
$hasIssues = false;

// Required columns for posts table
$requiredPostsColumns = [
    'id', 'title', 'slug', 'content', 'excerpt', 'featured_image',
    'author_id', 'status', 'is_ai_generated', 'campaign_id', 'site_id',
    'created_at', 'updated_at', 'published_at', 'views',
    // SEO fields
    'seo_title', 'meta_description', 'keywords',
    'focus_keyword', 'canonical_url', 'meta_robots',
    // Open Graph
    'og_title', 'og_description', 'og_image',
    // Twitter
    'twitter_title', 'twitter_description', 'twitter_image',
    // Schema
    'schema_type', 'faq_data',
    // Metrics
    'readability_score', 'word_count', 'reading_time',
    'internal_links_count', 'external_links_count', 'images_count',
    'has_table_of_contents', 'seo_score', 'last_seo_check'
];

// Check posts table structure
try {
    $result = $db->query("DESCRIBE posts");
    $existingColumns = [];

    foreach ($result as $column) {
        $existingColumns[] = $column->Field;
    }

    $missingColumns = array_diff($requiredPostsColumns, $existingColumns);

    if (!empty($missingColumns)) {
        $hasIssues = true;
        $messages[] = [
            'type' => 'error',
            'text' => 'Missing columns in posts table: ' . implode(', ', $missingColumns)
        ];
    } else {
        $messages[] = [
            'type' => 'success',
            'text' => 'Posts table structure is correct! All ' . count($existingColumns) . ' columns present.'
        ];
    }

} catch (Exception $e) {
    $hasIssues = true;
    $messages[] = [
        'type' => 'error',
        'text' => 'Error checking posts table: ' . $e->getMessage()
    ];
}

// Check other important tables
$requiredTables = ['posts', 'pages', 'categories', 'users', 'settings', 'menus', 'menu_items', 'campaigns', 'ai_queue'];
$existingTables = [];

try {
    $result = $db->query("SHOW TABLES");
    foreach ($result as $row) {
        $existingTables[] = array_values((array)$row)[0];
    }

    $missingTables = array_diff($requiredTables, $existingTables);

    if (!empty($missingTables)) {
        $hasIssues = true;
        $messages[] = [
            'type' => 'error',
            'text' => 'Missing tables: ' . implode(', ', $missingTables)
        ];
    } else {
        $messages[] = [
            'type' => 'success',
            'text' => 'All required tables exist! (' . count($existingTables) . ' tables total)'
        ];
    }

} catch (Exception $e) {
    $hasIssues = true;
    $messages[] = [
        'type' => 'error',
        'text' => 'Error checking tables: ' . $e->getMessage()
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check - LightBlog CMS</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .content {
            padding: 2rem;
        }
        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .message-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
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
        .message.warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .message.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .fix-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: center;
        }
        .fix-section h2 {
            color: #333;
            margin-bottom: 1rem;
        }
        .fix-section p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 3rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        button:active {
            transform: translateY(0);
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 0.5rem;
        }
        .info-box p {
            color: #424242;
            line-height: 1.6;
        }
        .info-box ol {
            margin: 1rem 0 0 1.5rem;
            color: #424242;
        }
        .info-box li {
            margin: 0.5rem 0;
        }
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Database Structure Check</h1>
            <p>Verify and fix your database structure</p>
        </div>

        <div class="content">
            <h2 style="margin-bottom: 1.5rem; color: #333;">Check Results</h2>

            <?php foreach ($messages as $message): ?>
                <div class="message <?= $message['type'] ?>">
                    <div class="message-icon">
                        <?php if ($message['type'] === 'success'): ?>✅<?php endif; ?>
                        <?php if ($message['type'] === 'error'): ?>❌<?php endif; ?>
                        <?php if ($message['type'] === 'warning'): ?>⚠️<?php endif; ?>
                        <?php if ($message['type'] === 'info'): ?>ℹ️<?php endif; ?>
                    </div>
                    <div><?= htmlspecialchars($message['text']) ?></div>
                </div>
            <?php endforeach; ?>

            <?php if ($hasIssues): ?>
                <div class="fix-section">
                    <h2>🔧 Database Needs Update</h2>
                    <p>
                        Your database structure is outdated or incomplete. Click the button below to run the migration
                        tool. It will safely add missing columns and tables without affecting your existing data.
                    </p>
                    <a href="database-migrate.php" style="display: inline-block; margin-top: 1rem;">
                        <button type="button" style="cursor: pointer;">
                            🚀 Run Migration Tool
                        </button>
                    </a>
                </div>

                <div class="info-box">
                    <h3>What this does:</h3>
                    <ol>
                        <li>Adds missing columns to existing tables</li>
                        <li>Creates any missing tables</li>
                        <li>Preserves all your existing data</li>
                        <li>Uses the latest schema from install/schema.sql</li>
                    </ol>
                    <p style="margin-top: 1rem;">
                        <strong>Safe to run:</strong> This update uses "CREATE TABLE IF NOT EXISTS" and
                        "ADD COLUMN IF NOT EXISTS" (when possible), so it won't overwrite existing data.
                    </p>
                </div>
            <?php else: ?>
                <div class="fix-section" style="background: #d4edda; border: 2px solid #28a745;">
                    <h2 style="color: #155724;">✅ Database is Up to Date!</h2>
                    <p style="color: #155724;">
                        Your database structure is correct and complete. All required tables and columns are present.
                        You're ready to use all features of LightBlog CMS!
                    </p>
                </div>

                <div class="info-box">
                    <h3>Database Status:</h3>
                    <p>
                        ✅ Posts table: <?= count($existingColumns) ?> columns<br>
                        ✅ All required tables: <?= count($existingTables) ?> tables<br>
                        ✅ Ready for AI content generation<br>
                        ✅ Ready for SEO optimization<br>
                        ✅ Ready for auto-blogging campaigns
                    </p>
                </div>
            <?php endif; ?>

            <a href="index.php" class="back-link">← Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
