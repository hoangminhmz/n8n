<?php
/**
 * Database Migration Manager
 * Auto-detects and fixes missing database structure
 */

class DatabaseMigration {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Check if database needs migration
     */
    public function needsMigration() {
        try {
            // Quick check for key columns
            $result = $this->db->query("SHOW COLUMNS FROM posts LIKE 'focus_keyword'");
            if (empty($result)) {
                return true;
            }

            // Check for pages table
            $result = $this->db->query("SHOW TABLES LIKE 'pages'");
            if (empty($result)) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Run automatic migration
     */
    public function migrate() {
        $results = [
            'success' => true,
            'columns_added' => 0,
            'tables_created' => 0,
            'errors' => []
        ];

        // Add missing columns to posts
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
                $this->db->query("ALTER TABLE `posts` ADD COLUMN `{$column['name']}` {$column['type']}");
                $results['columns_added']++;
            } catch (Exception $e) {
                // Column exists, skip
            }
        }

        // Add missing columns to categories
        try {
            $this->db->query("ALTER TABLE `categories` ADD COLUMN `icon` VARCHAR(50)");
            $results['columns_added']++;
        } catch (Exception $e) {}

        try {
            $this->db->query("ALTER TABLE `categories` ADD COLUMN `display_in_menu` TINYINT(1) DEFAULT 1");
            $results['columns_added']++;
        } catch (Exception $e) {}

        // Create pages table
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `pages` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results['tables_created']++;
        } catch (Exception $e) {
            // Table exists
        }

        // Create menus table
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `menus` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `location` VARCHAR(50),
                `description` TEXT,
                `created_at` DATETIME,
                `updated_at` DATETIME,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results['tables_created']++;
        } catch (Exception $e) {}

        // Create menu_items table
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `menu_items` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $results['tables_created']++;
        } catch (Exception $e) {}

        return $results;
    }

    /**
     * Get migration status
     */
    public function getStatus() {
        $status = [
            'posts_columns' => 0,
            'missing_posts_columns' => [],
            'has_pages' => false,
            'has_menus' => false,
            'needs_migration' => false
        ];

        try {
            // Check posts columns
            $result = $this->db->query("DESCRIBE posts");
            $existingColumns = [];
            foreach ($result as $column) {
                $existingColumns[] = $column->Field;
            }
            $status['posts_columns'] = count($existingColumns);

            $requiredColumns = ['focus_keyword', 'og_title', 'twitter_title', 'schema_type', 'seo_score'];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $existingColumns)) {
                    $status['missing_posts_columns'][] = $col;
                }
            }

            // Check pages table
            $result = $this->db->query("SHOW TABLES LIKE 'pages'");
            $status['has_pages'] = !empty($result);

            // Check menus table
            $result = $this->db->query("SHOW TABLES LIKE 'menus'");
            $status['has_menus'] = !empty($result);

            $status['needs_migration'] = !empty($status['missing_posts_columns']) || !$status['has_pages'] || !$status['has_menus'];

        } catch (Exception $e) {
            $status['needs_migration'] = true;
            $status['error'] = $e->getMessage();
        }

        return $status;
    }
}
