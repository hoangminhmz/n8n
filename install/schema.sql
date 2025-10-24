-- LightBlog CMS Database Schema
-- Compatible with both SQLite and MySQL

-- Core Tables

CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT,
    excerpt TEXT,
    featured_image TEXT,
    author_id INTEGER,
    status TEXT DEFAULT 'draft',
    is_ai_generated INTEGER DEFAULT 0,
    campaign_id INTEGER,
    site_id INTEGER DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    published_at DATETIME,
    views INTEGER DEFAULT 0,
    seo_title TEXT,
    meta_description TEXT,
    keywords TEXT
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    parent_id INTEGER
);

CREATE TABLE IF NOT EXISTS post_categories (
    post_id INTEGER,
    category_id INTEGER,
    PRIMARY KEY (post_id, category_id)
);

CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS post_tags (
    post_id INTEGER,
    tag_id INTEGER,
    PRIMARY KEY (post_id, tag_id)
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'editor',
    created_at DATETIME
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    autoload INTEGER DEFAULT 1
);

-- Auto-Blogging Tables

CREATE TABLE IF NOT EXISTS campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    niche TEXT,
    status TEXT DEFAULT 'active',
    goal TEXT,
    seed_keywords TEXT,
    target_count INTEGER,
    frequency TEXT,
    posts_per_day INTEGER,
    content_types TEXT,
    word_count_min INTEGER DEFAULT 1500,
    word_count_max INTEGER DEFAULT 2500,
    keyword_density REAL DEFAULT 1.5,
    auto_internal_links INTEGER DEFAULT 3,
    ai_provider TEXT,
    ai_model TEXT,
    ai_temperature REAL DEFAULT 0.7,
    tone TEXT,
    language TEXT DEFAULT 'en',
    affiliate_settings TEXT,
    start_date DATE,
    end_date DATE,
    publish_times TEXT,
    timezone TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS ai_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER,
    topic TEXT,
    keywords TEXT,
    status TEXT DEFAULT 'pending',
    priority INTEGER DEFAULT 5,
    scheduled_for DATETIME,
    generated_post_id INTEGER,
    error_message TEXT,
    created_at DATETIME,
    processed_at DATETIME
);

CREATE TABLE IF NOT EXISTS ai_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider TEXT,
    model TEXT,
    tokens_used INTEGER,
    cost REAL,
    campaign_id INTEGER,
    timestamp DATETIME
);

CREATE TABLE IF NOT EXISTS content_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT,
    structure TEXT,
    seo_patterns TEXT,
    created_at DATETIME
);

-- Affiliate System Tables

CREATE TABLE IF NOT EXISTS affiliate_networks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    api_key TEXT,
    tracking_id TEXT,
    commission_rate REAL,
    cookie_duration INTEGER,
    status TEXT DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS affiliate_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER,
    product_name TEXT,
    product_url TEXT,
    affiliate_url TEXT,
    image_url TEXT,
    price REAL,
    category TEXT,
    keywords TEXT,
    clicks INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    revenue REAL DEFAULT 0,
    last_updated DATETIME
);

CREATE TABLE IF NOT EXISTS campaign_affiliates (
    campaign_id INTEGER,
    product_id INTEGER,
    priority INTEGER DEFAULT 5,
    min_mentions INTEGER DEFAULT 1,
    PRIMARY KEY (campaign_id, product_id)
);

CREATE TABLE IF NOT EXISTS affiliate_clicks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER,
    product_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    referrer TEXT,
    clicked_at DATETIME
);

-- Satellite Network Tables

CREATE TABLE IF NOT EXISTS satellite_sites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    domain TEXT UNIQUE NOT NULL,
    api_key TEXT,
    niche TEXT,
    language TEXT DEFAULT 'en',
    status TEXT DEFAULT 'active',
    posts_count INTEGER DEFAULT 0,
    main_site_id INTEGER,
    created_at DATETIME
);

CREATE TABLE IF NOT EXISTS cross_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_site_id INTEGER,
    from_post_id INTEGER,
    to_site_id INTEGER,
    to_post_id INTEGER,
    anchor_text TEXT,
    position TEXT,
    created_at DATETIME
);

-- Cron Logs

CREATE TABLE IF NOT EXISTS cron_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_name TEXT,
    status TEXT,
    message TEXT,
    executed_at DATETIME
);

-- Media Library

CREATE TABLE IF NOT EXISTS media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_filename TEXT,
    file_path TEXT,
    file_size INTEGER,
    mime_type TEXT,
    width INTEGER,
    height INTEGER,
    uploaded_by INTEGER,
    uploaded_at DATETIME
);

-- Indexes for Performance

CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_posts_slug ON posts(slug);
CREATE INDEX IF NOT EXISTS idx_posts_campaign ON posts(campaign_id);
CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at);
CREATE INDEX IF NOT EXISTS idx_ai_queue_status ON ai_queue(status);
CREATE INDEX IF NOT EXISTS idx_ai_queue_scheduled ON ai_queue(scheduled_for);
CREATE INDEX IF NOT EXISTS idx_affiliate_clicks_post ON affiliate_clicks(post_id);
CREATE INDEX IF NOT EXISTS idx_affiliate_clicks_product ON affiliate_clicks(product_id);
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON campaigns(status);
-- LightBlog CMS - Pages System Migration (MySQL/MariaDB)
-- Add support for static pages (About, Contact, etc.)

-- Pages table
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
    `content_mode` VARCHAR(20) DEFAULT 'html',  -- 'html', 'markdown', 'ai'

    -- AI Generation (for future use)
    `ai_prompt` TEXT,
    `ai_generated` TINYINT(1) DEFAULT 0,
    `ai_provider` VARCHAR(50),
    `ai_model` VARCHAR(50),
    `template_style` VARCHAR(50),
    `last_generated_at` DATETIME,
    `generation_count` INT(11) DEFAULT 0,

    -- Metadata
    `author_id` INT(11),
    `status` VARCHAR(20) DEFAULT 'draft',  -- draft, published, private
    `visibility` VARCHAR(20) DEFAULT 'public',  -- public, private, password
    `password` VARCHAR(255),

    -- SEO fields (same as posts)
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
-- LightBlog CMS - Menu System Migration (MySQL/MariaDB)
-- Add support for navigation menus

-- Menus table (menu locations)
CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `location` VARCHAR(50),          -- 'primary', 'footer', 'mobile', 'sidebar'
    `description` TEXT,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_menus_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu Items table
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `menu_id` INT(11) UNSIGNED NOT NULL,

    -- Item type & target
    `type` VARCHAR(20) NOT NULL,     -- 'page', 'category', 'post', 'custom'
    `object_id` INT(11),             -- ID of page/category/post
    `custom_url` VARCHAR(500),       -- For custom links

    -- Display
    `title` VARCHAR(255) NOT NULL,   -- Label to display
    `css_classes` VARCHAR(255),      -- Custom CSS classes
    `target` VARCHAR(20) DEFAULT '_self',  -- _self, _blank

    -- Hierarchy
    `parent_id` INT(11) DEFAULT 0,   -- For dropdown menus
    `menu_order` INT(11) DEFAULT 0,  -- Sort order

    `created_at` DATETIME,

    PRIMARY KEY (`id`),
    KEY `idx_menu_items_menu` (`menu_id`),
    KEY `idx_menu_items_order` (`menu_order`),
    KEY `idx_menu_items_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add icon field to categories (for menu display)
ALTER TABLE `categories` ADD COLUMN `icon` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `categories` ADD COLUMN `display_in_menu` TINYINT(1) DEFAULT 1;

-- Insert default menus
INSERT INTO `menus` (`name`, `location`, `description`, `created_at`)
VALUES
('Primary Menu', 'primary', 'Main navigation menu in header', NOW()),
('Footer Menu', 'footer', 'Footer navigation menu', NOW());

-- Get the primary menu ID
SET @primary_menu_id = (SELECT `id` FROM `menus` WHERE `location` = 'primary' LIMIT 1);

-- Insert default menu items (Home + sample pages if they exist)
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `custom_url`, `title`, `menu_order`, `created_at`)
VALUES
(@primary_menu_id, 'custom', NULL, '/', 'Home', 0, NOW());

-- Add About page to menu if it exists
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `title`, `menu_order`, `created_at`)
SELECT @primary_menu_id, 'page', `id`, 'About', 1, NOW()
FROM `pages` WHERE `slug` = 'about' LIMIT 1;

-- Add Contact page to menu if it exists
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `title`, `menu_order`, `created_at`)
SELECT @primary_menu_id, 'page', `id`, 'Contact', 2, NOW()
FROM `pages` WHERE `slug` = 'contact' LIMIT 1;
-- LightBlog CMS - SEO Enhancement Migration
-- Run this to add SEO fields to existing database

-- Add SEO fields to posts table
ALTER TABLE posts ADD COLUMN focus_keyword TEXT;
ALTER TABLE posts ADD COLUMN canonical_url TEXT;
ALTER TABLE posts ADD COLUMN meta_robots TEXT DEFAULT 'index,follow';

-- Open Graph
ALTER TABLE posts ADD COLUMN og_title TEXT;
ALTER TABLE posts ADD COLUMN og_description TEXT;
ALTER TABLE posts ADD COLUMN og_image TEXT;

-- Twitter Cards
ALTER TABLE posts ADD COLUMN twitter_title TEXT;
ALTER TABLE posts ADD COLUMN twitter_description TEXT;
ALTER TABLE posts ADD COLUMN twitter_image TEXT;

-- Schema & Structured Data
ALTER TABLE posts ADD COLUMN schema_type TEXT DEFAULT 'Article';
ALTER TABLE posts ADD COLUMN faq_data TEXT;

-- Content Quality Metrics
ALTER TABLE posts ADD COLUMN readability_score REAL;
ALTER TABLE posts ADD COLUMN word_count INTEGER;
ALTER TABLE posts ADD COLUMN reading_time INTEGER;
ALTER TABLE posts ADD COLUMN internal_links_count INTEGER DEFAULT 0;
ALTER TABLE posts ADD COLUMN external_links_count INTEGER DEFAULT 0;
ALTER TABLE posts ADD COLUMN images_count INTEGER DEFAULT 0;
ALTER TABLE posts ADD COLUMN has_table_of_contents INTEGER DEFAULT 0;

-- SEO Score
ALTER TABLE posts ADD COLUMN seo_score INTEGER DEFAULT 0;
ALTER TABLE posts ADD COLUMN last_seo_check DATETIME;
