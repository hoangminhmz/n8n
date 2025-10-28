-- LightBlog CMS Database Schema v1.0
-- Compatible with MySQL/MariaDB
-- Complete schema with all features included

-- Core Tables

CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Basic Info
    `title` VARCHAR(500) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `content` LONGTEXT,
    `excerpt` TEXT,
    `featured_image` VARCHAR(500),

    -- Metadata
    `author_id` INT(11),
    `status` VARCHAR(20) DEFAULT 'draft',
    `is_ai_generated` TINYINT(1) DEFAULT 0,
    `campaign_id` INT(11),
    `site_id` INT(11) DEFAULT 1,

    -- Timestamps
    `created_at` DATETIME,
    `updated_at` DATETIME,
    `published_at` DATETIME,

    -- Stats
    `views` INT(11) DEFAULT 0,

    -- Basic SEO
    `seo_title` VARCHAR(255),
    `meta_description` TEXT,
    `keywords` TEXT,
    `focus_keyword` VARCHAR(255),
    `canonical_url` VARCHAR(500),
    `meta_robots` VARCHAR(50) DEFAULT 'index,follow',

    -- Open Graph
    `og_title` VARCHAR(255),
    `og_description` TEXT,
    `og_image` VARCHAR(500),

    -- Twitter Cards
    `twitter_title` VARCHAR(255),
    `twitter_description` TEXT,
    `twitter_image` VARCHAR(500),

    -- Schema & Structured Data
    `schema_type` VARCHAR(50) DEFAULT 'Article',
    `faq_data` TEXT,

    -- Content Quality Metrics
    `readability_score` DECIMAL(5,2),
    `word_count` INT(11),
    `reading_time` INT(11),
    `internal_links_count` INT(11) DEFAULT 0,
    `external_links_count` INT(11) DEFAULT 0,
    `images_count` INT(11) DEFAULT 0,
    `has_table_of_contents` TINYINT(1) DEFAULT 0,

    -- SEO Score
    `seo_score` INT(11) DEFAULT 0,
    `last_seo_check` DATETIME,

    PRIMARY KEY (`id`),
    KEY `idx_posts_status` (`status`),
    KEY `idx_posts_slug` (`slug`),
    KEY `idx_posts_campaign` (`campaign_id`),
    KEY `idx_posts_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `parent_id` INT(11) DEFAULT 0,
    `icon` VARCHAR(50) DEFAULT NULL,
    `display_in_menu` TINYINT(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_categories` (
    `post_id` INT(11) UNSIGNED,
    `category_id` INT(11) UNSIGNED,
    PRIMARY KEY (`post_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_tags` (
    `post_id` INT(11) UNSIGNED,
    `tag_id` INT(11) UNSIGNED,
    PRIMARY KEY (`post_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'editor',
    `created_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(255) PRIMARY KEY,
    `value` TEXT,
    `autoload` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pages System

CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Basic info
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `excerpt` TEXT,

    -- Hierarchy
    `parent_id` INT(11) DEFAULT 0,
    `menu_order` INT(11) DEFAULT 0,

    -- Template & Styling
    `template` VARCHAR(50) DEFAULT 'default',
    `custom_css` TEXT,
    `custom_js` TEXT,

    -- Content mode
    `content_mode` VARCHAR(20) DEFAULT 'html',

    -- AI Generation
    `ai_prompt` TEXT,
    `ai_generated` TINYINT(1) DEFAULT 0,
    `ai_provider` VARCHAR(50),
    `ai_model` VARCHAR(50),
    `template_style` VARCHAR(50),
    `last_generated_at` DATETIME,
    `generation_count` INT(11) DEFAULT 0,

    -- Metadata
    `author_id` INT(11),
    `status` VARCHAR(20) DEFAULT 'draft',
    `visibility` VARCHAR(20) DEFAULT 'public',
    `password` VARCHAR(255),

    -- SEO fields
    `seo_title` VARCHAR(255),
    `meta_description` TEXT,
    `canonical_url` VARCHAR(500),
    `meta_robots` VARCHAR(50) DEFAULT 'index,follow',
    `focus_keyword` VARCHAR(255),

    -- Open Graph
    `og_title` VARCHAR(255),
    `og_description` TEXT,
    `og_image` VARCHAR(500),

    -- Twitter Cards
    `twitter_title` VARCHAR(255),
    `twitter_description` TEXT,
    `twitter_image` VARCHAR(500),

    -- Schema
    `schema_type` VARCHAR(50) DEFAULT 'WebPage',

    -- Timestamps
    `created_at` DATETIME,
    `updated_at` DATETIME,
    `published_at` DATETIME,

    -- Stats
    `views` INT(11) DEFAULT 0,

    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_pages_status` (`status`),
    KEY `idx_pages_parent` (`parent_id`),
    KEY `idx_pages_menu_order` (`menu_order`),
    KEY `idx_pages_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu System

CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `location` VARCHAR(50),
    `description` TEXT,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_menus_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `menu_id` INT(11) UNSIGNED NOT NULL,

    -- Item type & target
    `type` VARCHAR(20) NOT NULL,
    `object_id` INT(11),
    `custom_url` VARCHAR(500),

    -- Display
    `title` VARCHAR(255) NOT NULL,
    `css_classes` VARCHAR(255),
    `target` VARCHAR(20) DEFAULT '_self',

    -- Hierarchy
    `parent_id` INT(11) DEFAULT 0,
    `menu_order` INT(11) DEFAULT 0,

    `created_at` DATETIME,

    PRIMARY KEY (`id`),
    KEY `idx_menu_items_menu` (`menu_id`),
    KEY `idx_menu_items_order` (`menu_order`),
    KEY `idx_menu_items_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-Blogging Tables

CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `niche` TEXT,
    `status` VARCHAR(20) DEFAULT 'active',
    `goal` TEXT,
    `seed_keywords` TEXT,
    `target_count` INT(11),
    `frequency` VARCHAR(50),
    `posts_per_day` INT(11),
    `content_types` TEXT,
    `word_count_min` INT(11) DEFAULT 1500,
    `word_count_max` INT(11) DEFAULT 2500,
    `keyword_density` DECIMAL(5,2) DEFAULT 1.5,
    `auto_internal_links` INT(11) DEFAULT 3,
    `ai_provider` VARCHAR(50),
    `ai_model` VARCHAR(50),
    `ai_temperature` DECIMAL(3,2) DEFAULT 0.7,
    `tone` VARCHAR(50),
    `language` VARCHAR(10) DEFAULT 'en',
    `affiliate_settings` TEXT,
    `start_date` DATE,
    `end_date` DATE,
    `publish_times` TEXT,
    `timezone` VARCHAR(50),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_campaigns_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_queue` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT(11),
    `topic` VARCHAR(500),
    `keywords` TEXT,
    `status` VARCHAR(20) DEFAULT 'pending',
    `priority` INT(11) DEFAULT 5,
    `scheduled_for` DATETIME,
    `generated_post_id` INT(11),
    `error_message` TEXT,
    `created_at` DATETIME,
    `processed_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_ai_queue_status` (`status`),
    KEY `idx_ai_queue_scheduled` (`scheduled_for`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_usage` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider` VARCHAR(50),
    `model` VARCHAR(50),
    `tokens_used` INT(11),
    `cost` DECIMAL(10,6),
    `campaign_id` INT(11),
    `timestamp` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_ai_usage_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_templates` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50),
    `structure` TEXT,
    `seo_patterns` TEXT,
    `created_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate System Tables

CREATE TABLE IF NOT EXISTS `affiliate_networks` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `api_key` VARCHAR(255),
    `tracking_id` VARCHAR(255),
    `commission_rate` DECIMAL(5,2),
    `cookie_duration` INT(11),
    `status` VARCHAR(20) DEFAULT 'active',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `network_id` INT(11),
    `product_name` VARCHAR(500),
    `product_url` VARCHAR(500),
    `affiliate_url` VARCHAR(500),
    `image_url` VARCHAR(500),
    `price` DECIMAL(10,2),
    `category` VARCHAR(255),
    `keywords` TEXT,
    `clicks` INT(11) DEFAULT 0,
    `conversions` INT(11) DEFAULT 0,
    `revenue` DECIMAL(10,2) DEFAULT 0,
    `last_updated` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_affiliate_products_network` (`network_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_affiliates` (
    `campaign_id` INT(11) UNSIGNED,
    `product_id` INT(11) UNSIGNED,
    `priority` INT(11) DEFAULT 5,
    `min_mentions` INT(11) DEFAULT 1,
    PRIMARY KEY (`campaign_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `affiliate_clicks` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id` INT(11),
    `product_id` INT(11),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `referrer` VARCHAR(500),
    `clicked_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_affiliate_clicks_post` (`post_id`),
    KEY `idx_affiliate_clicks_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satellite Network Tables

CREATE TABLE IF NOT EXISTS `satellite_sites` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `domain` VARCHAR(255) UNIQUE NOT NULL,
    `api_key` VARCHAR(255),
    `niche` VARCHAR(255),
    `language` VARCHAR(10) DEFAULT 'en',
    `status` VARCHAR(20) DEFAULT 'active',
    `posts_count` INT(11) DEFAULT 0,
    `main_site_id` INT(11),
    `created_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_satellite_sites_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cross_links` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_site_id` INT(11),
    `from_post_id` INT(11),
    `to_site_id` INT(11),
    `to_post_id` INT(11),
    `anchor_text` VARCHAR(255),
    `position` VARCHAR(50),
    `created_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_cross_links_from` (`from_site_id`, `from_post_id`),
    KEY `idx_cross_links_to` (`to_site_id`, `to_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cron Logs

CREATE TABLE IF NOT EXISTS `cron_logs` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_name` VARCHAR(255),
    `status` VARCHAR(20),
    `message` TEXT,
    `executed_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_cron_logs_executed` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Media Library

CREATE TABLE IF NOT EXISTS `media` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255),
    `file_path` VARCHAR(500),
    `file_size` BIGINT,
    `mime_type` VARCHAR(100),
    `width` INT(11),
    `height` INT(11),
    `uploaded_by` INT(11),
    `uploaded_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_media_uploaded` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data

-- Insert default user (admin/password123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `created_at`)
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());

-- Insert sample pages
INSERT INTO `pages` (`title`, `slug`, `content`, `status`, `published_at`, `created_at`, `seo_title`, `meta_description`)
VALUES
('About Us', 'about',
'<h1>About Us</h1>
<p>Welcome to our blog! We are passionate about creating quality content.</p>
<h2>Our Mission</h2>
<p>To provide valuable insights and information to our readers.</p>',
'published', NOW(), NOW(),
'About Us - Learn More About Our Blog',
'Learn more about our mission, values, and the team behind our blog.'
),
('Contact', 'contact',
'<h1>Contact Us</h1>
<p>Get in touch with us for inquiries, feedback, or collaboration opportunities.</p>
<h2>Contact Information</h2>
<ul>
<li><strong>Email:</strong> contact@example.com</li>
<li><strong>Twitter:</strong> @example</li>
</ul>',
'published', NOW(), NOW(),
'Contact Us - Get in Touch',
'Contact us for inquiries, feedback, or collaboration opportunities.'
);

-- Insert default menus
INSERT INTO `menus` (`name`, `location`, `description`, `created_at`)
VALUES
('Primary Menu', 'primary', 'Main navigation menu in header', NOW()),
('Footer Menu', 'footer', 'Footer navigation menu', NOW());

-- Insert default menu items
SET @primary_menu_id = LAST_INSERT_ID();

INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `custom_url`, `title`, `menu_order`, `created_at`)
VALUES
(@primary_menu_id, 'custom', NULL, '/', 'Home', 0, NOW());

-- Add About page to menu
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `title`, `menu_order`, `created_at`)
SELECT @primary_menu_id, 'page', `id`, 'About', 1, NOW()
FROM `pages` WHERE `slug` = 'about' LIMIT 1;

-- Add Contact page to menu
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `title`, `menu_order`, `created_at`)
SELECT @primary_menu_id, 'page', `id`, 'Contact', 2, NOW()
FROM `pages` WHERE `slug` = 'contact' LIMIT 1;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('site_name', 'LightBlog CMS'),
('site_tagline', 'AI-Powered Content Hub'),
('posts_per_page', '10'),
('timezone', 'UTC'),
('image_ai_provider', 'auto'),
('content_ai_provider', 'auto');
