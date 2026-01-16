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

-- AI Prompt Templates System

CREATE TABLE IF NOT EXISTS `prompt_templates` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Theme Customization System

CREATE TABLE IF NOT EXISTS `theme_customizations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `base_theme` VARCHAR(50) DEFAULT 'default',

    -- CSS Variables (JSON)
    `css_variables` TEXT,

    -- Custom CSS
    `custom_css` LONGTEXT,

    -- Layout & Design Settings
    `layout_style` VARCHAR(20) DEFAULT 'card', -- 'card', 'list', 'grid'
    `layout_spacing` VARCHAR(20) DEFAULT 'normal', -- 'compact', 'normal', 'spacious'
    `border_radius` VARCHAR(10) DEFAULT '8px', -- '0', '4px', '8px', '16px'

    -- Font Settings
    `font_family` VARCHAR(100) DEFAULT 'system-ui',
    `font_scale` DECIMAL(3,2) DEFAULT 1.00, -- 0.90 to 1.20

    -- Status
    `is_active` TINYINT(1) DEFAULT 0,
    `is_preview` TINYINT(1) DEFAULT 0,

    -- Metadata
    `created_by` INT(11),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    `ai_prompt` TEXT, -- Original user prompt

    PRIMARY KEY (`id`),
    KEY `idx_active` (`is_active`),
    KEY `idx_created_by` (`created_by`)
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

-- Insert default menu items (using subquery to get primary menu ID)
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `custom_url`, `title`, `menu_order`, `created_at`)
SELECT `id`, 'custom', NULL, '/', 'Home', 0, NOW()
FROM `menus` WHERE `location` = 'primary' LIMIT 1;

-- Add About page to menu
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `title`, `menu_order`, `created_at`)
SELECT m.`id`, 'page', p.`id`, 'About', 1, NOW()
FROM `menus` m, `pages` p
WHERE m.`location` = 'primary' AND p.`slug` = 'about' LIMIT 1;

-- Add Contact page to menu
INSERT INTO `menu_items` (`menu_id`, `type`, `object_id`, `title`, `menu_order`, `created_at`)
SELECT m.`id`, 'page', p.`id`, 'Contact', 2, NOW()
FROM `menus` m, `pages` p
WHERE m.`location` = 'primary' AND p.`slug` = 'contact' LIMIT 1;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('site_name', 'LightBlog CMS'),
('site_tagline', 'AI-Powered Content Hub'),
('posts_per_page', '10'),
('timezone', 'UTC'),
('image_ai_provider', 'auto'),
('content_ai_provider', 'auto');

-- Insert default AI prompt templates
INSERT INTO `prompt_templates` (`template_key`, `template_name`, `category`, `default_prompt`, `variables`, `description`, `example_output`, `is_active`, `created_at`) VALUES
('post_title', 'Post Title Generation', 'post', 'Generate SEO metadata for an article about: "{{topic}}"\n\nPrimary keyword: {{primary_keyword}}\n\nCreate:\n1. An SEO-optimized title (55-60 characters, include keyword)\n2. A meta description (150-160 characters, compelling, include keyword)\n3. An alternative SEO title for rich snippets\n\nFormat as JSON:\n{\n  "title": "Catchy title here",\n  "seo_title": "SEO optimized title",\n  "meta_description": "Compelling description"\n}', '["{{topic}}", "{{primary_keyword}}", "{{niche}}", "{{tone}}", "{{year}}"]', 'Generates SEO-optimized title and meta description for blog posts', '{"title": "Best Coffee Makers 2025", "seo_title": "Best Coffee Makers 2025 - Complete Buying Guide", "meta_description": "Discover the top coffee makers for 2025..."}', 0, NOW()),

('post_outline', 'Content Outline Generation', 'post', 'Create a detailed SEO-optimized article outline for: "{{topic}}"\n\nPrimary keywords: {{primary_keywords}}\nLSI keywords: {{lsi_keywords}}\n\nRequirements:\n- Include H1, H2, and H3 headings\n- Add an FAQ section with 5 questions\n- Target word count: {{word_count_min}}-{{word_count_max}} words\n- Optimize for featured snippets\n- Include introduction and conclusion\n\nFormat as JSON:\n{\n  "h1": "Main title",\n  "sections": [\n    {"h2": "Section title", "h3": ["Subsection 1", "Subsection 2"]},\n    ...\n  ],\n  "faqs": [\n    {"question": "Q1", "answer_hint": "brief hint"},\n    ...\n  ]\n}', '["{{topic}}", "{{primary_keywords}}", "{{lsi_keywords}}", "{{word_count_min}}", "{{word_count_max}}", "{{niche}}"]', 'Creates detailed content outline with headings and FAQ structure', '{"h1": "Best Coffee Makers", "sections": [...]}', 0, NOW()),

('post_content', 'Article Content Writing', 'post', 'Write a comprehensive, engaging blog article based on this outline:\n\n{{outline}}\n\nRequirements:\n- Tone: {{tone}}\n- Naturally include these keywords: {{primary_keywords}}\n- Write in clear, engaging paragraphs\n- Add relevant examples and statistics\n- Include [PRODUCT_LINK] markers where affiliate products should be mentioned\n- Format as HTML with proper heading tags (h1, h2, h3)\n- Add bullet points and numbered lists where appropriate\n- Make it SEO-optimized and reader-friendly\n\nWrite the complete article content now:', '["{{outline}}", "{{tone}}", "{{primary_keywords}}", "{{niche}}", "{{word_count}}"]', 'Generates full article content from outline', '<h1>Best Coffee Makers</h1><p>Coffee enthusiasts know...</p>', 0, NOW()),

('post_meta_description', 'Meta Description Generation', 'post', 'Create a compelling meta description (150-160 characters) for an article titled "{{title}}".\n\nFocus keyword: {{focus_keyword}}\nNiche: {{niche}}\n\nRequirements:\n- Exactly 150-160 characters\n- Include the focus keyword naturally\n- Make it click-worthy and informative\n- Use active voice\n- Create urgency or curiosity\n\nOutput ONLY the meta description text, no quotes:', '["{{title}}", "{{focus_keyword}}", "{{niche}}"]', 'Creates optimized meta descriptions for search results', 'Discover the best coffee makers for 2025. Expert reviews, comparisons, and buying guide to find your perfect brew.', 0, NOW()),

('post_excerpt', 'Post Excerpt Generation', 'post', 'Create a compelling excerpt (140-160 characters) for this article:\n\nTitle: {{title}}\nFirst paragraph: {{first_paragraph}}\n\nRequirements:\n- 140-160 characters\n- Engaging and informative\n- Complete sentence\n- Encourage reading the full article\n\nOutput ONLY the excerpt text:', '["{{title}}", "{{first_paragraph}}", "{{content}}"]', 'Generates engaging excerpts for post previews', 'Learn how to choose the perfect coffee maker for your home. Expert tips and top recommendations included.', 0, NOW()),

('post_faq', 'FAQ Generation', 'post', 'Generate 5 frequently asked questions and answers for an article about "{{topic}}".\n\nNiche: {{niche}}\nTarget audience: {{target_audience}}\n\nRequirements:\n- Questions should be natural and commonly searched\n- Answers should be concise (2-3 sentences)\n- Cover different aspects of the topic\n- SEO-friendly question format\n\nFormat as JSON array:\n[\n  {"question": "Question 1?", "answer": "Answer 1"},\n  ...\n]', '["{{topic}}", "{{niche}}", "{{target_audience}}", "{{content}}"]', 'Creates FAQ section for articles', '[{"question": "What is the best coffee maker?", "answer": "..."}]', 0, NOW()),

('campaign_topics', 'Campaign Topic Generation', 'campaign', 'Generate {{count}} unique, engaging blog topic ideas for the {{niche}} niche.\n\nSeed keywords: {{seed_keywords}}\nTarget audience: {{target_audience}}\n\nAVOID these existing topics (be creative and different):\n{{existing_topics}}\n\nRequirements:\n- Each topic should be specific and actionable\n- Include search-friendly keywords naturally\n- Mix formats: how-to, listicles, guides, comparisons\n- Consider current trends in {{year}}\n- Topics should rank well in Google\n\nOutput as JSON array:\n["Topic 1", "Topic 2", ...]', '["{{count}}", "{{niche}}", "{{seed_keywords}}", "{{target_audience}}", "{{existing_topics}}", "{{year}}"]', 'Generates unique topic ideas for campaigns avoiding duplicates', '["10 Best Coffee Makers for Small Kitchens", "How to Clean Your Coffee Maker: Complete Guide"]', 0, NOW()),

('campaign_keywords', 'LSI Keyword Research', 'campaign', 'Generate LSI (Latent Semantic Indexing) keywords for the topic: "{{topic}}"\n\nPrimary keyword: {{primary_keyword}}\nNiche: {{niche}}\n\nRequirements:\n- Generate 10-15 related keywords\n- Include long-tail variations\n- Natural language variations\n- Question-based keywords\n- Intent-based keywords\n\nOutput as JSON array:\n["keyword1", "keyword2", ...]', '["{{topic}}", "{{primary_keyword}}", "{{niche}}"]', 'Generates LSI keywords for better SEO coverage', '["coffee maker reviews", "best drip coffee makers", "how to choose coffee maker"]', 0, NOW()),

('image_generation', 'AI Image Generation Prompt', 'image', 'Create a {{style}} image that visually represents: {{topic}}.\n\nThe image should be a high-quality photograph or illustration directly related to this topic.\n\nNO TEXT, NO WORDS, NO LETTERS anywhere in the image.\n\nFocus on visual storytelling - show the concept through imagery alone.\n\nEye-catching, professional, suitable for blog featured image and social media.\n\n16:9 aspect ratio, cinematic composition, visually appealing.', '["{{topic}}", "{{style}}", "{{niche}}"]', 'Creates prompts for AI image generation (DALL-E, Midjourney)', 'Professional image of modern coffee maker on kitchen counter...', 0, NOW()),

('image_search', 'Image Search Keywords', 'image', 'Extract 3-4 main keywords from this topic for searching stock photos: "{{topic}}"\n\nRequirements:\n- Remove filler words (the, and, for, with, etc.)\n- Focus on visual, concrete nouns\n- Suitable for Unsplash/Pexels search\n- Avoid abstract concepts\n\nOutput keywords separated by spaces:', '["{{topic}}", "{{niche}}"]', 'Extracts keywords for searching stock photo databases', 'coffee maker kitchen modern', 0, NOW());
