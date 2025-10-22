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
