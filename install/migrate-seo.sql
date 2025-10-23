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
