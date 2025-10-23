-- LightBlog CMS - Pages System Migration
-- Add support for static pages (About, Contact, etc.)

-- Pages table
CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    -- Basic info
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT,
    excerpt TEXT,

    -- Hierarchy
    parent_id INTEGER DEFAULT 0,
    menu_order INTEGER DEFAULT 0,

    -- Template & Styling
    template TEXT DEFAULT 'default',
    custom_css TEXT,
    custom_js TEXT,

    -- Content mode
    content_mode TEXT DEFAULT 'html',  -- 'html', 'markdown', 'ai'

    -- AI Generation (for future use)
    ai_prompt TEXT,
    ai_generated INTEGER DEFAULT 0,
    ai_provider TEXT,
    ai_model TEXT,
    template_style TEXT,
    last_generated_at DATETIME,
    generation_count INTEGER DEFAULT 0,

    -- Metadata
    author_id INTEGER,
    status TEXT DEFAULT 'draft',  -- draft, published, private
    visibility TEXT DEFAULT 'public',  -- public, private, password
    password TEXT,

    -- SEO fields (same as posts)
    seo_title TEXT,
    meta_description TEXT,
    canonical_url TEXT,
    meta_robots TEXT DEFAULT 'index,follow',
    focus_keyword TEXT,

    -- Open Graph
    og_title TEXT,
    og_description TEXT,
    og_image TEXT,

    -- Twitter Cards
    twitter_title TEXT,
    twitter_description TEXT,
    twitter_image TEXT,

    -- Schema
    schema_type TEXT DEFAULT 'WebPage',

    -- Timestamps
    created_at DATETIME,
    updated_at DATETIME,
    published_at DATETIME,

    -- Stats
    views INTEGER DEFAULT 0
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug);
CREATE INDEX IF NOT EXISTS idx_pages_status ON pages(status);
CREATE INDEX IF NOT EXISTS idx_pages_parent ON pages(parent_id);
CREATE INDEX IF NOT EXISTS idx_pages_menu_order ON pages(menu_order);
CREATE INDEX IF NOT EXISTS idx_pages_published ON pages(published_at);

-- Insert sample pages
INSERT INTO pages (title, slug, content, status, published_at, created_at, seo_title, meta_description)
VALUES
('About Us', 'about',
'<h1>About Us</h1>
<p>Welcome to our blog! We are passionate about creating quality content.</p>
<h2>Our Mission</h2>
<p>To provide valuable insights and information to our readers.</p>',
'published', datetime('now'), datetime('now'),
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
'published', datetime('now'), datetime('now'),
'Contact Us - Get in Touch',
'Contact us for inquiries, feedback, or collaboration opportunities.'
);
