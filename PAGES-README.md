# 📄 Pages System - Installation Guide

## ✅ What's New

The **Pages System** allows you to create static pages (About, Contact, Privacy, etc.) separate from blog posts.

### Features:
- ✅ Full CRUD interface for pages
- ✅ Hierarchical pages (parent/child structure)
- ✅ Clean URLs: `/{slug}` (pages have priority over posts)
- ✅ Page templates support (default, full-width, contact, landing)
- ✅ SEO integration (all 23 SEO fields from posts)
- ✅ Custom CSS/JS per page
- ✅ Menu ordering
- ✅ Visibility controls (public, private)

---

## 🚀 Installation Steps

### Step 1: Run Database Migration

You need to create the `pages` table in your database.

**Option A: Using phpMyAdmin**
1. Go to phpMyAdmin
2. Select your database: `hoangmi5_lightblog`
3. Click "SQL" tab
4. Copy and paste content from: `install/migrate-pages.sql`
5. Click "Go"

**Option B: Using MySQL Command Line**
```bash
mysql -u hoangmi5_admin -p hoangmi5_lightblog < install/migrate-pages.sql
```

**Option C: Using PHP Script (on server)**
```bash
php run-migration.php
```

### Step 2: Verify Installation

After migration, verify that:
1. Table `pages` exists in database
2. Sample pages "About Us" and "Contact" are created
3. Admin panel shows "Pages" menu item

---

## 📖 Usage Guide

### Creating a Page

1. Go to **Admin → Pages → New Page**
2. Fill in:
   - **Title**: Page title (e.g., "About Us")
   - **Slug**: URL slug (auto-generated from title, or custom)
   - **Content**: Page content (HTML supported)
   - **Parent**: Select parent page for hierarchical structure
   - **Template**: Choose template (default, full-width, contact, landing)
   - **Status**: Draft or Published
   - **SEO**: Fill in SEO fields (title, description, OG, Twitter)

3. Click "Create Page"

### Page URLs

Pages are accessible at: `https://hoangminhmz.com/lite/{slug}`

Examples:
- About page: `https://hoangminhmz.com/lite/about`
- Contact: `https://hoangminhmz.com/lite/contact`
- Privacy: `https://hoangminhmz.com/lite/privacy-policy`

**Note**: Pages have priority over posts. If both page and post have same slug, page will be shown.

### Hierarchical Pages

You can create parent-child page structure:
```
About Us                → /about
  ├─ Our Team          → /our-team (parent_id = about.id)
  └─ Our Story         → /our-story (parent_id = about.id)

Services               → /services
  ├─ Web Development   → /web-development
  └─ SEO Services      → /seo-services
```

### Page Templates

Create custom templates in `themes/default/`:

- `page.php` - Default template
- `page-full-width.php` - Full width (no sidebar)
- `page-contact.php` - Contact page with form
- `page-landing.php` - Landing page style

Template selection in page editor will use corresponding file.

---

## 🎨 Theme Integration

### In header.php

Pages are automatically available. You can check if current page:

```php
<?php if (is_page()): ?>
    <h1>This is a page</h1>
<?php endif; ?>

<?php if (is_page_slug('about')): ?>
    <h1>This is the About page</h1>
<?php endif; ?>
```

### Getting page data

```php
<?php
$page = get_current_page();
echo $page->title;
echo $page->content;
echo $page->slug;
?>
```

### Custom CSS per page

Each page can have custom CSS that will be loaded on that page only.

In page editor → Custom CSS field:
```css
.page-content {
    background: #f0f0f0;
    padding: 2rem;
}

h1 {
    color: #3b82f6;
}
```

---

## 🔗 Next: Menu System

After pages are working, the next step is **Menu System** to display pages in navigation.

Menu system will allow:
- Add pages to navigation menus
- Add categories to menus
- Add custom links
- Nested menus (dropdowns)
- Multiple menu locations (header, footer, sidebar)

---

## 📊 Database Schema

```sql
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    -- Basic
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT,
    excerpt TEXT,

    -- Hierarchy
    parent_id INTEGER DEFAULT 0,
    menu_order INTEGER DEFAULT 0,

    -- Template
    template TEXT DEFAULT 'default',
    custom_css TEXT,
    custom_js TEXT,

    -- Status
    status TEXT DEFAULT 'draft',
    visibility TEXT DEFAULT 'public',

    -- SEO (same fields as posts)
    seo_title TEXT,
    meta_description TEXT,
    canonical_url TEXT,
    ... (23 SEO fields total)

    -- Stats
    views INTEGER DEFAULT 0,

    -- Timestamps
    created_at DATETIME,
    updated_at DATETIME,
    published_at DATETIME
);
```

---

## ❓ Troubleshooting

### Issue: "Pages" menu not showing in admin

**Solution**: Clear browser cache and refresh

### Issue: Page URLs return 404

**Solution**:
1. Check `.htaccess` file exists
2. Check mod_rewrite is enabled
3. Verify page status is "published"
4. Check page slug doesn't conflict with existing routes

### Issue: Sample pages not created

**Solution**: Run migration again or create pages manually

---

## ✅ Testing Checklist

After installation, verify:

- [ ] Can access admin/pages.php
- [ ] Can create new page
- [ ] Can edit existing page
- [ ] Can delete page
- [ ] Sample pages "About" and "Contact" exist
- [ ] Can access page at `/about` URL
- [ ] Page increments view count
- [ ] SEO fields work (view page source, check meta tags)
- [ ] Custom CSS applies to page
- [ ] Hierarchical pages work (parent/child)

---

## 🎯 What's Next

The menu system is coming next, which will allow you to:
1. Create navigation menus
2. Add pages to menus
3. Add categories to menus
4. Display menus in theme header/footer

Stay tuned!

---

**Made with ❤️ for LightBlog CMS**
