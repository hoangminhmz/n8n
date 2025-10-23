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
