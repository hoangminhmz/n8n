-- LightBlog CMS - Menu System Migration
-- Add support for navigation menus

-- Menus table (menu locations)
CREATE TABLE IF NOT EXISTS menus (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(50),          -- 'primary', 'footer', 'mobile', 'sidebar'
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

-- Menu Items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    menu_id INTEGER NOT NULL,

    -- Item type & target
    type VARCHAR(20) NOT NULL,     -- 'page', 'category', 'post', 'custom'
    object_id INTEGER,             -- ID of page/category/post
    custom_url VARCHAR(500),       -- For custom links

    -- Display
    title VARCHAR(255) NOT NULL,   -- Label to display
    css_classes VARCHAR(255),      -- Custom CSS classes
    target VARCHAR(20) DEFAULT '_self',  -- _self, _blank

    -- Hierarchy
    parent_id INTEGER DEFAULT 0,   -- For dropdown menus
    menu_order INTEGER DEFAULT 0,  -- Sort order

    created_at DATETIME
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_menu_items_menu ON menu_items(menu_id);
CREATE INDEX IF NOT EXISTS idx_menu_items_order ON menu_items(menu_order);
CREATE INDEX IF NOT EXISTS idx_menu_items_parent ON menu_items(parent_id);
CREATE INDEX IF NOT EXISTS idx_menus_location ON menus(location);

-- Add icon field to categories (for menu display)
ALTER TABLE categories ADD COLUMN icon VARCHAR(50) DEFAULT NULL;
ALTER TABLE categories ADD COLUMN display_in_menu TINYINT(1) DEFAULT 1;

-- Insert default menus
INSERT INTO menus (name, location, description, created_at)
VALUES
('Primary Menu', 'primary', 'Main navigation menu in header', NOW()),
('Footer Menu', 'footer', 'Footer navigation menu', NOW());

-- Get the primary menu ID (will be 1 if this is first run)
SET @primary_menu_id = (SELECT id FROM menus WHERE location = 'primary' LIMIT 1);

-- Insert default menu items (Home + sample pages if they exist)
INSERT INTO menu_items (menu_id, type, object_id, custom_url, title, menu_order, created_at)
VALUES
(@primary_menu_id, 'custom', NULL, '/', 'Home', 0, NOW());

-- Add About page to menu if it exists
INSERT INTO menu_items (menu_id, type, object_id, title, menu_order, created_at)
SELECT @primary_menu_id, 'page', id, 'About', 1, NOW()
FROM pages WHERE slug = 'about' LIMIT 1;

-- Add Contact page to menu if it exists
INSERT INTO menu_items (menu_id, type, object_id, title, menu_order, created_at)
SELECT @primary_menu_id, 'page', id, 'Contact', 2, NOW()
FROM pages WHERE slug = 'contact' LIMIT 1;
