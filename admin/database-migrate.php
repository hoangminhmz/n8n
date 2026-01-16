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

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $messages[] = ['type' => 'error', 'text' => "Error creating menus table: " . $e->getMessage()];
            $errors++;
        }
    }

    // Insert default menus if they don't exist
    try {
        $existingMenus = $db->queryOne("SELECT COUNT(*) as count FROM menus WHERE location IN ('primary', 'footer')");
        if ($existingMenus->count == 0) {
            $db->query("INSERT INTO `menus` (`name`, `location`, `description`, `created_at`) VALUES
                ('Primary Menu', 'primary', 'Main navigation menu', NOW()),
                ('Footer Menu', 'footer', 'Footer navigation menu', NOW())
            ");
            $messages[] = ['type' => 'success', 'text' => "Added default menus"];
            $executed++;
        } else {
            $messages[] = ['type' => 'info', 'text' => "Default menus already exist (skipped)"];
        }
    } catch (Exception $e) {
        $messages[] = ['type' => 'warning', 'text' => "Could not insert default menus: " . $e->getMessage()];
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

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $messages[] = ['type' => 'error', 'text' => "Error creating menu_items table: " . $e->getMessage()];
            $errors++;
        }
    }

    // Insert default menu items if they don't exist
    try {
        $primaryMenuId = $db->queryOne("SELECT id FROM menus WHERE location = 'primary' LIMIT 1");
        if ($primaryMenuId && $primaryMenuId->id) {
            // Check if menu items already exist for this menu
            $existingItems = $db->queryOne("SELECT COUNT(*) as count FROM menu_items WHERE menu_id = ?", [$primaryMenuId->id]);
            if ($existingItems->count == 0) {
                $db->query("INSERT INTO `menu_items` (`menu_id`, `type`, `custom_url`, `title`, `menu_order`, `created_at`) VALUES
                    ({$primaryMenuId->id}, 'custom', '/', 'Home', 0, NOW())
                ");
                $messages[] = ['type' => 'success', 'text' => "Added default menu items"];
                $executed++;
            } else {
                $messages[] = ['type' => 'info', 'text' => "Default menu items already exist (skipped)"];
            }
        } else {
            $messages[] = ['type' => 'warning', 'text' => "Primary menu not found, skipping menu items creation"];
        }
    } catch (Exception $e) {
        $messages[] = ['type' => 'warning', 'text' => "Could not insert default menu items: " . $e->getMessage()];
    }

    // Step 6: Create prompt_templates table for AI prompt customization
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `prompt_templates` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `template_key` VARCHAR(100) UNIQUE NOT NULL,
            `template_name` VARCHAR(255) NOT NULL,
            `category` VARCHAR(50) NOT NULL,
            `default_prompt` TEXT NOT NULL,
            `custom_prompt` TEXT,
            `is_active` TINYINT(1) DEFAULT 0,
            `variables` TEXT,
            `description` TEXT,
            `example_output` TEXT,
            `created_at` DATETIME,
            `updated_at` DATETIME,
            `updated_by` INT(11),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_template_key` (`template_key`),
            KEY `idx_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->query($sql);
        $messages[] = ['type' => 'success', 'text' => "Created table: prompt_templates"];
        $executed++;

        // Insert default AI prompt templates
        $checkExisting = $db->queryOne("SELECT COUNT(*) as count FROM prompt_templates");
        if ($checkExisting->count == 0) {
            // Read default prompts from schema.sql section
            $defaultPrompts = [
                [
                    'template_key' => 'post_title',
                    'template_name' => 'Post Title Generation',
                    'category' => 'post',
                    'default_prompt' => "Generate SEO metadata for an article about: \"{{topic}}\"\n\nPrimary keyword: {{primary_keyword}}\n\nCreate:\n1. An SEO-optimized title (55-60 characters, include keyword)\n2. A meta description (150-160 characters, compelling, include keyword)\n3. An alternative SEO title for rich snippets\n\nFormat as JSON:\n{\n  \"title\": \"Catchy title here\",\n  \"seo_title\": \"SEO optimized title\",\n  \"meta_description\": \"Compelling description\"\n}",
                    'variables' => '["{{topic}}", "{{primary_keyword}}", "{{niche}}", "{{tone}}", "{{year}}"]',
                    'description' => 'Generates SEO-optimized title and meta description for blog posts'
                ],
                [
                    'template_key' => 'post_outline',
                    'template_name' => 'Content Outline Generation',
                    'category' => 'post',
                    'default_prompt' => "Create a detailed SEO-optimized article outline for: \"{{topic}}\"\n\nPrimary keywords: {{primary_keywords}}\nLSI keywords: {{lsi_keywords}}\n\nRequirements:\n- Include H1, H2, and H3 headings\n- Add an FAQ section with 5 questions\n- Target word count: {{word_count_min}}-{{word_count_max}} words\n- Optimize for featured snippets\n- Include introduction and conclusion\n\nFormat as JSON:\n{\n  \"h1\": \"Main title\",\n  \"sections\": [\n    {\"h2\": \"Section title\", \"h3\": [\"Subsection 1\", \"Subsection 2\"]},\n    ...\n  ],\n  \"faqs\": [\n    {\"question\": \"Q1\", \"answer_hint\": \"brief hint\"},\n    ...\n  ]\n}",
                    'variables' => '["{{topic}}", "{{primary_keywords}}", "{{lsi_keywords}}", "{{word_count_min}}", "{{word_count_max}}", "{{niche}}"]',
                    'description' => 'Creates detailed content outline with headings and FAQ structure'
                ],
                [
                    'template_key' => 'post_content',
                    'template_name' => 'Article Content Writing',
                    'category' => 'post',
                    'default_prompt' => "Write a comprehensive, engaging blog article based on this outline:\n\n{{outline}}\n\nRequirements:\n- Tone: {{tone}}\n- Naturally include these keywords: {{primary_keywords}}\n- Write in clear, engaging paragraphs\n- Add relevant examples and statistics\n- Include [PRODUCT_LINK] markers where affiliate products should be mentioned\n- Format as HTML with proper heading tags (h1, h2, h3)\n- Add bullet points and numbered lists where appropriate\n- Make it SEO-optimized and reader-friendly\n\nWrite the complete article content now:",
                    'variables' => '["{{outline}}", "{{tone}}", "{{primary_keywords}}", "{{niche}}", "{{word_count}}"]',
                    'description' => 'Generates full article content from outline'
                ],
                [
                    'template_key' => 'campaign_topics',
                    'template_name' => 'Campaign Topic Generation',
                    'category' => 'campaign',
                    'default_prompt' => "Generate {{count}} unique, engaging blog topic ideas for the {{niche}} niche.\n\nSeed keywords: {{seed_keywords}}\nTarget audience: {{target_audience}}\n\nAVOID these existing topics (be creative and different):\n{{existing_topics}}\n\nRequirements:\n- Each topic should be specific and actionable\n- Include search-friendly keywords naturally\n- Mix formats: how-to, listicles, guides, comparisons\n- Consider current trends in {{year}}\n- Topics should rank well in Google\n\nOutput as JSON array:\n[\"Topic 1\", \"Topic 2\", ...]",
                    'variables' => '["{{count}}", "{{niche}}", "{{seed_keywords}}", "{{target_audience}}", "{{existing_topics}}", "{{year}}"]',
                    'description' => 'Generates unique topic ideas for campaigns avoiding duplicates'
                ],
                [
                    'template_key' => 'image_generation',
                    'template_name' => 'AI Image Generation Prompt',
                    'category' => 'image',
                    'default_prompt' => "Create a {{style}} image that visually represents: {{topic}}.\n\nThe image should be a high-quality photograph or illustration directly related to this topic.\n\nNO TEXT, NO WORDS, NO LETTERS anywhere in the image.\n\nFocus on visual storytelling - show the concept through imagery alone.\n\nEye-catching, professional, suitable for blog featured image and social media.\n\n16:9 aspect ratio, cinematic composition, visually appealing.",
                    'variables' => '["{{topic}}", "{{style}}", "{{niche}}"]',
                    'description' => 'Creates prompts for AI image generation (DALL-E, Midjourney)'
                ],
                [
                    'template_key' => 'image_search',
                    'template_name' => 'Image Search Keywords',
                    'category' => 'image',
                    'default_prompt' => "Extract 3-4 main keywords from this topic for searching stock photos: \"{{topic}}\"\n\nRequirements:\n- Remove filler words (the, and, for, with, etc.)\n- Focus on visual, concrete nouns\n- Suitable for Unsplash/Pexels search\n- Avoid abstract concepts\n\nOutput keywords separated by spaces:",
                    'variables' => '["{{topic}}", "{{niche}}"]',
                    'description' => 'Extracts keywords for searching stock photo databases'
                ]
            ];

            foreach ($defaultPrompts as $prompt) {
                try {
                    $db->insert('prompt_templates', [
                        'template_key' => $prompt['template_key'],
                        'template_name' => $prompt['template_name'],
                        'category' => $prompt['category'],
                        'default_prompt' => $prompt['default_prompt'],
                        'variables' => $prompt['variables'],
                        'description' => $prompt['description'],
                        'is_active' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    // Skip if already exists
                }
            }

            $messages[] = ['type' => 'success', 'text' => "Inserted " . count($defaultPrompts) . " default AI prompt templates"];
        } else {
            $messages[] = ['type' => 'info', 'text' => "Prompt templates already exist (skipped)"];
        }

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') === false) {
            $messages[] = ['type' => 'error', 'text' => "Error creating prompt_templates table: " . $e->getMessage()];
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
                        <li>Create <code>prompt_templates</code> table with 6 default AI prompts</li>
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
