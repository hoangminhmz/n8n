# LightBlog CMS - Technical Architecture

**Version:** 1.0
**Last Updated:** 2025-10-24
**Purpose:** Comprehensive technical documentation for developers and AI assistants

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Directory Structure](#directory-structure)
3. [Database Schema](#database-schema)
4. [Core Components](#core-components)
5. [AI Provider Architecture](#ai-provider-architecture)
6. [Admin Panel](#admin-panel)
7. [Frontend System](#frontend-system)
8. [Programming Patterns](#programming-patterns)
9. [Extension Guide](#extension-guide)
10. [Development Roadmap](#development-roadmap)

---

## System Overview

### Technology Stack

- **Backend:** PHP 7.4+ (Object-Oriented)
- **Database:** MySQL/MariaDB with SQLite compatibility layer
- **Frontend:** Vanilla JavaScript (ES6+), No frameworks
- **Styling:** CSS3 with responsive design
- **AI Integration:** OpenAI, Anthropic Claude, Google Gemini, Unsplash API

### Architecture Pattern

**MVC-Inspired Structure** (Lightweight, no framework):
- **Models:** `core/` classes (Database, Post, User, etc.)
- **Views:** `themes/` templates + `admin/` panels
- **Controllers:** Admin PHP files + AJAX handlers

**Key Design Principles:**
1. **Provider Abstraction:** AI providers are interchangeable via unified interface
2. **Configuration-Driven:** Settings stored in both `config.php` and database
3. **Progressive Enhancement:** Works without JavaScript, enhanced with JS
4. **Multi-Provider Fallback:** Auto-detect → Specific provider → Fallback

---

## Directory Structure

```
/
├── admin/                      # Admin panel (requires authentication)
│   ├── index.php              # Dashboard
│   ├── posts.php              # Post management (bulk operations)
│   ├── pages.php              # Static pages management
│   ├── menus.php              # Menu builder (drag & drop)
│   ├── categories.php         # Category management
│   ├── settings.php           # System settings (AI provider config)
│   ├── ajax-*.php             # AJAX endpoints (bulk ops, menu save)
│   └── includes/
│       ├── header.php         # Admin header (auth check)
│       └── footer.php         # Admin footer
│
├── core/                       # Core classes (autoloaded)
│   ├── Database.php           # PDO wrapper (singleton)
│   ├── Router.php             # URL routing (slug → content)
│   ├── Post.php               # Post CRUD operations
│   ├── Page.php               # Page CRUD operations
│   ├── Menu.php               # Menu management
│   ├── User.php               # User authentication
│   ├── SEO.php                # SEO optimization
│   └── AI/
│       ├── AIProvider.php     # Base provider class
│       ├── OpenAIProvider.php # OpenAI GPT-4 + DALL-E 3
│       ├── ClaudeProvider.php # Anthropic Claude
│       ├── GeminiProvider.php # Google Gemini
│       ├── UnsplashProvider.php # Unsplash stock photos (FREE)
│       ├── ImageGenerator.php # Image orchestration
│       └── ContentGenerator.php # Content orchestration
│
├── themes/                     # Frontend themes
│   └── default/
│       ├── header.php         # Site header (menu rendering)
│       ├── footer.php         # Site footer
│       ├── index.php          # Blog listing
│       ├── single.php         # Single post
│       ├── page.php           # Static page
│       ├── archive.php        # Archive page
│       ├── category.php       # Category archive
│       └── style.css          # Theme styles
│
├── install/                    # Installation files
│   ├── schema.sql             # Complete database schema (single source)
│   └── config.sample.php      # Configuration template
│
├── content/                    # User content (writable)
│   └── uploads/               # Uploaded files + AI-generated images
│
├── config.php                  # System configuration (API keys)
├── index.php                   # Frontend entry point (routing)
├── autoload.php               # PSR-4-like autoloader
└── README.md                  # Quick start guide

```

**Key Files:**

- **index.php:** Frontend router (slug → post/page)
- **admin/posts.php:** Post management with bulk AI operations
- **core/AI/ImageGenerator.php:** Multi-provider image generation
- **admin/settings.php:** UI for AI provider configuration
- **install/schema.sql:** Single source of truth for database structure

---

## Database Schema

### Core Content Tables

**posts** - Blog posts (AI-generated or manual)
```sql
Columns: id, title, slug, content, excerpt, featured_image
         author_id, status, is_ai_generated, campaign_id
         created_at, updated_at, published_at, views
         seo_title, meta_description, keywords
         focus_keyword, canonical_url, meta_robots
         og_title, og_description, og_image
         twitter_title, twitter_description, twitter_image
         schema_type, faq_data
         readability_score, word_count, reading_time
         internal_links_count, external_links_count, images_count
         has_table_of_contents, seo_score, last_seo_check
```

**pages** - Static pages (About, Contact, etc.)
```sql
Columns: id, title, slug, content, excerpt
         parent_id, menu_order, template, custom_css, custom_js
         content_mode (html/markdown/ai)
         ai_prompt, ai_generated, ai_provider, ai_model
         template_style, last_generated_at, generation_count
         author_id, status, visibility, password
         [Same SEO fields as posts]
         created_at, updated_at, published_at, views
```

**categories** - Post categories
```sql
Columns: id, name, slug, description, parent_id
         icon, display_in_menu
```

**tags** - Post tags
```sql
Columns: id, name, slug
```

### Menu System

**menus** - Menu locations (primary, footer, mobile)
```sql
Columns: id, name, location, description
         created_at, updated_at
```

**menu_items** - Individual menu links
```sql
Columns: id, menu_id, type (page/category/post/custom)
         object_id, custom_url, title, css_classes, target
         parent_id (for dropdowns), menu_order
         created_at
```

### AI & Auto-Blogging

**campaigns** - AI content generation campaigns
```sql
Columns: id, name, niche, status, goal, seed_keywords
         target_count, frequency, posts_per_day, content_types
         word_count_min, word_count_max, keyword_density
         auto_internal_links, ai_provider, ai_model, ai_temperature
         tone, language, affiliate_settings
         start_date, end_date, publish_times, timezone
         created_at, updated_at
```

**ai_queue** - Scheduled AI generation jobs
```sql
Columns: id, campaign_id, topic, keywords
         status, priority, scheduled_for
         generated_post_id, error_message
         created_at, processed_at
```

**ai_usage** - Cost tracking
```sql
Columns: id, provider, model, tokens_used, cost
         campaign_id, timestamp
```

### System Tables

**users** - Admin/editor accounts
```sql
Columns: id, username, email, password, role
         created_at
```

**settings** - System configuration (key-value store)
```sql
Columns: key, value, autoload
Key Settings:
- site_name, site_tagline, posts_per_page, timezone
- openai_api_key, claude_api_key, gemini_api_key, unsplash_api_key
- image_ai_provider (auto/unsplash/openai/php-gd)
- content_ai_provider (auto/gemini/claude/openai)
- autoblog_enabled
```

**media** - Media library (uploaded files + AI images)
```sql
Columns: id, filename, original_filename, file_path
         file_size, mime_type, width, height
         uploaded_by, uploaded_at
```

---

## Core Components

### 1. Database Class (`core/Database.php`)

**Pattern:** Singleton with PDO wrapper

```php
$db = Database::getInstance();

// Query with prepared statements
$db->query("SELECT * FROM posts WHERE id = ?", [$id]);

// Insert
$db->insert('posts', [
    'title' => 'Example',
    'slug' => 'example',
    'content' => '...'
]);

// Update
$db->update('posts', ['status' => 'published'], 'id = :id', ['id' => 5]);

// Fetch
$post = $db->fetch("SELECT * FROM posts WHERE slug = ?", [$slug]);
$posts = $db->fetchAll("SELECT * FROM posts WHERE status = 'published'");
```

**Key Methods:**
- `query($sql, $params)` - Execute with prepared statements
- `insert($table, $data)` - Array to INSERT
- `update($table, $data, $where, $params)` - Array to UPDATE
- `fetch($sql, $params)` - Single row (object)
- `fetchAll($sql, $params)` - Multiple rows (array)

### 2. Router Class (`core/Router.php`)

**Pattern:** Front controller

```php
$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);

// Routing Logic:
// /                    → Blog listing (themes/default/index.php)
// /post/slug-here      → Single post (themes/default/single.php)
// /page/about          → Static page (themes/default/page.php)
// /category/tech       → Category archive (themes/default/category.php)
// /archive/2024/10     → Date archive (themes/default/archive.php)
```

**How it works:**
1. Parse REQUEST_URI
2. Match against patterns (post/page/category/archive)
3. Load content from database
4. Include appropriate theme template
5. Pass `$content` variable to template

### 3. Post & Page Classes (`core/Post.php`, `core/Page.php`)

**CRUD Operations:**

```php
// Create
$post = new Post();
$post->title = "New Post";
$post->slug = "new-post";
$post->content = "...";
$postId = $post->save();

// Read
$post = Post::find($id);
$post = Post::findBySlug('slug-here');
$posts = Post::all(['status' => 'published'], 'created_at DESC', 10);

// Update
$post = Post::find($id);
$post->title = "Updated Title";
$post->save();

// Delete
$post->delete();
```

### 4. SEO Class (`core/SEO.php`)

**SEO Optimization:**

```php
$seo = new SEO();
$seo->renderMetaTags($post); // Output meta tags for <head>

// Auto-generate SEO data
$seoData = $seo->generateForPost($post); // Returns array with:
// - seo_title, meta_description, focus_keyword
// - og_title, og_description, og_image
// - twitter_title, twitter_description, twitter_image
// - canonical_url, meta_robots, schema_type
```

**SEO Features:**
- Title optimization (length, keyword placement)
- Meta description generation
- Open Graph tags (Facebook)
- Twitter Card tags
- Schema.org structured data
- Canonical URLs
- Robots directives (index/noindex)

---

## AI Provider Architecture

### Design Pattern: Strategy Pattern

All AI providers implement a common interface, allowing interchangeable use.

### Base Class: `AIProvider.php`

```php
abstract class AIProvider {
    abstract public function generate($prompt, $options = []);
    abstract public function validateApiKey();
    abstract public function getName();
    abstract public function estimateCost($options);
}
```

### Provider Implementations

#### 1. OpenAIProvider (`core/AI/OpenAIProvider.php`)

**Capabilities:**
- Text generation (GPT-4, GPT-3.5-turbo)
- Image generation (DALL-E 3)

**Usage:**
```php
$openai = new OpenAIProvider(OPENAI_API_KEY);

// Text generation
$result = $openai->generate("Write a blog post about AI", [
    'model' => 'gpt-4',
    'temperature' => 0.7,
    'max_tokens' => 2000
]);
// Returns: ['content' => '...', 'tokens' => 1500, 'cost' => 0.03]

// Image generation
$result = $openai->generateImage("A futuristic city", [
    'size' => '1024x1024',
    'quality' => 'standard'
]);
// Returns: ['image_url' => 'https://...', 'cost' => 0.04]
```

**Cost:** ~$0.03/1K tokens (GPT-4), $0.04/image (DALL-E 3)

#### 2. ClaudeProvider (`core/AI/ClaudeProvider.php`)

**Capabilities:**
- Text generation (Claude 3.5 Sonnet, Claude 3 Opus)
- Long context (200K tokens)

**Usage:**
```php
$claude = new ClaudeProvider(CLAUDE_API_KEY);
$result = $claude->generate("Write detailed article", [
    'model' => 'claude-3-5-sonnet-20241022',
    'temperature' => 0.7,
    'max_tokens' => 4000
]);
```

**Cost:** ~$0.015/1K tokens (Sonnet), balanced quality/price

#### 3. GeminiProvider (`core/AI/GeminiProvider.php`)

**Capabilities:**
- Text generation (Gemini 1.5 Pro, Gemini 1.5 Flash)
- Extremely cheap pricing

**Usage:**
```php
$gemini = new GeminiProvider(GEMINI_API_KEY);
$result = $gemini->generate("Write blog post", [
    'model' => 'gemini-1.5-flash',
    'temperature' => 0.7
]);
```

**Cost:** ~$0.0001/1K tokens (Flash), **cheapest option**

#### 4. UnsplashProvider (`core/AI/UnsplashProvider.php`)

**Capabilities:**
- Free stock photo search and download
- High-quality real images

**Usage:**
```php
$unsplash = new UnsplashProvider(UNSPLASH_API_KEY);
$result = $unsplash->searchAndDownload("mountain landscape", [
    'orientation' => 'landscape'
]);
// Returns: ['image_url' => '/content/uploads/...', 'cost' => 0.0, 'attribution' => '...']
```

**Cost:** **FREE** (500 requests/hour limit)
**Compliance:** Must display attribution (handled automatically)

### Orchestrator Classes

#### ImageGenerator (`core/AI/ImageGenerator.php`)

**Purpose:** Unified interface for image generation across providers

**Provider Priority:**
1. Unsplash (FREE) - if API key configured
2. OpenAI DALL-E 3 ($0.04/image) - if API key configured
3. PHP GD (FREE) - gradient + text fallback

**Usage:**
```php
$imageGen = new ImageGenerator('auto'); // or 'unsplash', 'openai', 'php-gd'

$result = $imageGen->generateThumbnail("Blog Post Title", [
    'size' => '1200x630',
    'quality' => 'standard',
    'style' => 'professional'
]);

// Returns: ['image_url' => '/content/uploads/...', 'cost' => 0.0, 'provider' => 'unsplash']
```

**Automatic Query Extraction:**
```php
// Title: "How to Build a REST API with Node.js"
// Extracted: "Build REST API Node" (4 keywords for Unsplash search)
```

#### ContentGenerator (`core/AI/ContentGenerator.php`)

**Purpose:** Unified interface for content generation

**Provider Priority:**
1. Gemini ($0.0001/post) - cheapest, fast
2. Claude ($0.015/post) - balanced
3. OpenAI ($0.03/post) - most capable

**Usage:**
```php
$contentGen = new ContentGenerator('auto'); // or 'gemini', 'claude', 'openai'

$result = $contentGen->generatePost([
    'title' => 'Introduction to Machine Learning',
    'keywords' => ['AI', 'ML', 'neural networks'],
    'tone' => 'educational',
    'word_count' => 2000
]);

// Returns: ['content' => '...', 'excerpt' => '...', 'cost' => 0.0001]
```

### Cost Optimization Strategy

**Recommended Setup:**
- **Images:** Unsplash (FREE) for stock photos
- **Content:** Gemini Flash (nearly FREE) for blog posts
- **Total Cost:** ~$0/post with Unsplash + Gemini

**Premium Setup:**
- **Images:** OpenAI DALL-E 3 for unique AI images
- **Content:** Claude or GPT-4 for highest quality
- **Total Cost:** ~$0.04-0.07/post

---

## Admin Panel

### Authentication System

**File:** `admin/includes/header.php`

```php
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load user
$user = User::find($_SESSION['user_id']);
```

**Security Features:**
- Session-based authentication
- Password hashing (bcrypt)
- CSRF protection (security token in forms)
- HTTP-only cookies
- SQL injection prevention (prepared statements)

### Key Admin Pages

#### 1. Posts Management (`admin/posts.php`)

**Features:**
- List all posts (published, draft, pending)
- Filter by status, category, search
- Bulk actions:
  - Change status (publish/draft)
  - Delete multiple
  - **Auto-generate thumbnails** (AI-powered)
  - **Auto-generate SEO data** (AI-powered)
- Individual actions: Edit, Delete, Preview

**Bulk Operations Implementation:**

```javascript
// admin/posts.php (frontend)
async function confirmBulkAction(event) {
    const action = document.getElementById('bulkAction').value;

    if (action === 'generate_thumbnails' || action === 'generate_seo') {
        event.preventDefault();

        const postIds = Array.from(checked).map(cb => parseInt(cb.value));

        const response = await fetch('ajax-bulk-operations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                operation: action,
                post_ids: postIds
            })
        });

        const result = await response.json();
        alert(`Success! Processed: ${result.processed}, Failed: ${result.failed}`);
        location.reload();
    }
}
```

```php
// admin/ajax-bulk-operations.php (backend)
switch ($operation) {
    case 'generate_thumbnails':
        foreach ($postIds as $postId) {
            $post = Post::find($postId);
            $imageGen = new ImageGenerator('auto');
            $result = $imageGen->generateThumbnail($post->title, [...]);

            $db->update('posts', [
                'featured_image' => $result['image_url']
            ], 'id = :id', ['id' => $postId]);
        }
        break;

    case 'generate_seo':
        foreach ($postIds as $postId) {
            $post = Post::find($postId);
            $seoData = generateSEOData($post); // Uses AI
            $db->update('posts', $seoData, 'id = :id', ['id' => $postId]);
        }
        break;
}
```

#### 2. Pages Management (`admin/pages.php`)

**Features:**
- Manage static pages (About, Contact, Privacy, etc.)
- Hierarchical structure (parent/child pages)
- Template selection (default, full-width, custom)
- SEO fields (same as posts)
- Custom CSS/JS per page

#### 3. Menu Builder (`admin/menus.php`)

**Features:**
- Drag & drop menu ordering
- Menu locations (primary, footer, mobile)
- Menu item types:
  - **Page:** Link to static page
  - **Category:** Link to category archive
  - **Post:** Link to specific post
  - **Custom:** External URL
- Dropdown menus (parent/child)
- CSS classes for styling

**AJAX Save Implementation:**

```javascript
// admin/menus.php (frontend)
async function saveMenu() {
    const menuData = {
        menu_id: currentMenuId,
        items: [] // Serialized from DOM
    };

    const response = await fetch('ajax-menu-save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(menuData)
    });

    const result = await response.json();
    if (result.success) {
        alert('Menu saved!');
    }
}
```

```php
// admin/ajax-menu-save.php (backend)
$menuId = $data['menu_id'];
$items = $data['items'];

// Delete existing items
$db->query("DELETE FROM menu_items WHERE menu_id = ?", [$menuId]);

// Insert new items
foreach ($items as $order => $item) {
    $db->insert('menu_items', [
        'menu_id' => $menuId,
        'type' => $item['type'],
        'object_id' => $item['object_id'] ?? null,
        'custom_url' => $item['custom_url'] ?? null,
        'title' => $item['title'],
        'parent_id' => $item['parent_id'] ?? 0,
        'menu_order' => $order,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}
```

#### 4. Settings Panel (`admin/settings.php`)

**Configuration Options:**

**General:**
- Site Name & Tagline
- Posts per page
- Timezone
- Auto-blogging enabled

**AI Providers - Image Generation:**
- Unsplash API Key (FREE stock photos)
- OpenAI API Key (DALL-E 3, $0.04/image)
- **Image AI Provider:** Dropdown selector
  - Auto-detect (Unsplash → OpenAI → PHP GD)
  - Unsplash (Free)
  - OpenAI (Premium)
  - PHP GD (Fallback)

**AI Providers - Content Generation:**
- Gemini API Key (Cheapest, $0.0001/post)
- Claude API Key (Balanced, $0.015/post)
- OpenAI API Key (Most capable, $0.03/post)
- **Content AI Provider:** Dropdown selector
  - Auto-detect (Gemini → Claude → OpenAI)
  - Gemini (Cheapest)
  - Claude (Balanced)
  - OpenAI (Premium)

**Save Behavior:**
```php
// admin/settings.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Save to database (settings table)
    foreach ($settingsToUpdate as $key) {
        $value = $_POST[$key] ?? '';
        $db->query(
            "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE `value` = ?",
            [$key, $value, $value]
        );
    }

    // 2. Update config.php file (for persistence)
    $configContent = file_get_contents('config.php');
    $configContent = preg_replace(
        "/define\('OPENAI_API_KEY', '.*?'\);/",
        "define('OPENAI_API_KEY', '{$_POST['openai_api_key']}');",
        $configContent
    );
    file_put_contents('config.php', $configContent);
}
```

**Why Both Database + config.php?**
- **Database:** User-friendly UI, easily editable
- **config.php:** Persistence across database resets, constant definitions

#### 5. Backup & Restore (`admin/backup.php`)

**Purpose:** Complete site backup and restore functionality for disaster recovery and migration.

**Features:**
- Create complete backup (source code + database)
- Download backup as ZIP file
- One-click restore from backup ZIP
- Automatic API key sanitization for security
- Automatic rollback on restore failure

**Backup Process:**

```php
// admin/backup.php
function createBackup() {
    // 1. Export database to SQL
    exportDatabase($tempDir . '/database.sql');

    // 2. Create backup metadata
    $metadata = [
        'version' => '1.0',
        'date' => date('Y-m-d H:i:s'),
        'site_name' => SITE_NAME,
        'database_size' => filesize($dbFile)
    ];

    // 3. Create ZIP with source code + database
    $zip = new ZipArchive();
    $zip->addFile($dbFile, 'database.sql');
    $zip->addFile($metadataFile, 'backup_info.json');

    // 4. Add source files (sanitize config.php)
    foreach ($sourceFiles as $file) {
        if ($file === 'config.php') {
            // Remove API keys for security
            $content = preg_replace(
                "/(define\(['\"].*API_KEY['\"],\s*['\"])([^'\"]*)/",
                "$1YOUR_API_KEY_HERE",
                $content
            );
            $zip->addFromString('source/' . $file, $content);
        } else {
            $zip->addFile($file, 'source/' . $file);
        }
    }

    $zip->close();
    return ['success' => true, 'file' => $zipFile];
}
```

**Restore Process:**

```php
function restoreFromBackup($zipFile) {
    // 1. Validate ZIP structure
    if (!file_exists($tempDir . '/database.sql')) {
        return ['success' => false, 'message' => 'Invalid backup'];
    }

    // 2. Create rollback point (current database)
    $rollbackDir = sys_get_temp_dir() . '/lightblog_rollback_' . time();
    exportDatabase($rollbackDir . '/database.sql');

    try {
        // 3. Restore database
        $sql = file_get_contents($tempDir . '/database.sql');
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($queries as $query) {
            $db->query($query);
        }

        // 4. Restore source code (skip config.php)
        foreach ($sourceFiles as $file) {
            if ($file !== 'config.php') {
                copy($tempFile, $targetFile);
            }
        }

        return ['success' => true];

    } catch (Exception $e) {
        // Rollback on error
        $sql = file_get_contents($rollbackDir . '/database.sql');
        // ... restore from rollback
        return ['success' => false, 'rolled_back' => true];
    }
}
```

**UI Integration:**

```javascript
// admin/settings.php (frontend)
document.getElementById('createBackupBtn').addEventListener('click', async function() {
    const response = await fetch('backup.php', {
        method: 'POST',
        body: 'action=create_backup'
    });

    const result = await response.json();

    if (result.success) {
        // Show download button
        window.location.href = 'backup.php?action=download_backup';
    }
});

document.getElementById('restoreForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!confirm('⚠️ This will replace ALL current data. Continue?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'restore_backup');
    formData.append('backup_file', fileInput.files[0]);

    const response = await fetch('backup.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        alert('✅ Restore completed! Please re-enter API keys.');
        location.reload();
    }
});
```

**Security Features:**
- API keys removed from backups (sanitized)
- Database credentials sanitized
- Automatic rollback on restore failure
- File size validation (500MB limit)
- ZIP structure validation
- User confirmation required for restore

**Backup Contents:**
```
lightblog_backup_2025-10-24_12-00-00.zip
├── README.txt                    # Restore instructions
├── backup_info.json              # Metadata (version, date, size)
├── database.sql                  # Complete database dump
└── source/                       # Source code
    ├── admin/
    ├── core/
    ├── themes/
    ├── install/
    ├── index.php
    └── config.php                # API keys removed
```

**Excluded from Backup:**
- `content/uploads/` - Large files (backup separately if needed)
- `.git/` - Version control history
- `node_modules/` - Development dependencies
- `.env` - Environment files
- `error_log` - Log files

**Use Cases:**
- Before major updates
- Daily/weekly automated backups (via cron)
- Site migration to new server
- Disaster recovery
- Testing restore process

**Automated Backup (Cron):**
```bash
# Daily backup at 2 AM
0 2 * * * php /var/www/lightblog/admin/backup.php --action=create_backup --output=/backups/ >> /var/log/backup.log 2>&1
```

**For Commercial Distribution:**
- White-label backup branding
- Backup usage limits by tier (free: 1/month, pro: unlimited)
- Cloud backup integration (AWS S3, Google Drive, Dropbox)
- Automated backup scheduling UI
- Backup retention policies

See **BACKUP-RESTORE.md** for complete documentation.

---

## Frontend System

### Theme Structure

**Location:** `themes/default/`

**Template Hierarchy:**
1. **index.php** - Blog listing (latest posts)
2. **single.php** - Individual blog post
3. **page.php** - Static page
4. **category.php** - Category archive
5. **archive.php** - Date-based archive
6. **header.php** - Site header (logo, menu)
7. **footer.php** - Site footer

### Template Variables

Available in all theme templates:

```php
// Global
$siteSettings = [
    'site_name' => 'Blog Name',
    'site_tagline' => 'Tagline',
    'posts_per_page' => 10
];

// In single.php and page.php
$content = (object)[
    'id' => 1,
    'title' => 'Post Title',
    'slug' => 'post-slug',
    'content' => 'Full content...',
    'excerpt' => 'Short excerpt...',
    'featured_image' => '/content/uploads/image.jpg',
    'author_id' => 1,
    'status' => 'published',
    'created_at' => '2024-10-24 12:00:00',
    'published_at' => '2024-10-24 12:00:00',
    'seo_title' => 'SEO Title',
    'meta_description' => 'Meta description...',
    'og_image' => '/content/uploads/og-image.jpg'
];

// In index.php and category.php
$posts = []; // Array of post objects
$pagination = [
    'current_page' => 1,
    'total_pages' => 5,
    'total_posts' => 50
];
```

### Menu Rendering

```php
// themes/default/header.php
$menu = Menu::getByLocation('primary');
$items = Menu::getItems($menu->id);

echo '<nav><ul>';
foreach ($items as $item) {
    $url = Menu::getItemUrl($item);
    echo "<li><a href='{$url}'>{$item->title}</a>";

    // Dropdown menu
    if ($item->children) {
        echo '<ul class="dropdown">';
        foreach ($item->children as $child) {
            $childUrl = Menu::getItemUrl($child);
            echo "<li><a href='{$childUrl}'>{$child->title}</a></li>";
        }
        echo '</ul>';
    }

    echo '</li>';
}
echo '</ul></nav>';
```

### SEO Output

```php
// themes/default/header.php
$seo = new SEO();
echo $seo->renderMetaTags($content);

// Outputs:
// <title>SEO Title | Site Name</title>
// <meta name="description" content="...">
// <meta property="og:title" content="...">
// <meta property="og:description" content="...">
// <meta property="og:image" content="...">
// <meta name="twitter:card" content="summary_large_image">
// <link rel="canonical" href="...">
// <script type="application/ld+json">{"@type": "Article", ...}</script>
```

---

## Programming Patterns

### 1. Singleton Pattern (Database)

**Why:** Ensure single database connection throughout request lifecycle

```php
class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // PDO connection
    }
}

// Usage
$db = Database::getInstance();
```

### 2. Strategy Pattern (AI Providers)

**Why:** Interchangeable AI providers without code changes

```php
interface AIProvider {
    public function generate($prompt, $options = []);
}

class OpenAIProvider implements AIProvider { /* ... */ }
class ClaudeProvider implements AIProvider { /* ... */ }
class GeminiProvider implements AIProvider { /* ... */ }

// Usage
$provider = new GeminiProvider(API_KEY);
$result = $provider->generate("Write content");
```

### 3. Factory Pattern (Provider Selection)

**Why:** Auto-detect and instantiate correct provider

```php
class ImageGenerator {
    private function initializeProvider() {
        if ($this->preferredProvider === 'auto') {
            if (!empty(UNSPLASH_API_KEY)) {
                return new UnsplashProvider(UNSPLASH_API_KEY);
            } elseif (!empty(OPENAI_API_KEY)) {
                return new OpenAIProvider(OPENAI_API_KEY);
            } else {
                return new PHPGDProvider();
            }
        }

        // Explicit provider
        switch ($this->preferredProvider) {
            case 'unsplash': return new UnsplashProvider(UNSPLASH_API_KEY);
            case 'openai': return new OpenAIProvider(OPENAI_API_KEY);
            case 'php-gd': return new PHPGDProvider();
        }
    }
}
```

### 4. Active Record Pattern (Models)

**Why:** Object-oriented database operations

```php
class Post {
    public $id;
    public $title;
    public $slug;
    public $content;

    public function save() {
        $db = Database::getInstance();
        if ($this->id) {
            // Update
            $db->update('posts', get_object_vars($this), 'id = :id', ['id' => $this->id]);
        } else {
            // Insert
            $this->id = $db->insert('posts', get_object_vars($this));
        }
        return $this->id;
    }

    public static function find($id) {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM posts WHERE id = ?", [$id]);
    }
}
```

### 5. Repository Pattern (Data Access)

**Why:** Centralize database queries

```php
class Post {
    public static function all($filters = [], $orderBy = '', $limit = null) {
        $db = Database::getInstance();

        $sql = "SELECT * FROM posts";
        $params = [];

        if (!empty($filters)) {
            $sql .= " WHERE ";
            foreach ($filters as $key => $value) {
                $sql .= "$key = :$key AND ";
                $params[$key] = $value;
            }
            $sql = rtrim($sql, ' AND ');
        }

        if ($orderBy) $sql .= " ORDER BY $orderBy";
        if ($limit) $sql .= " LIMIT $limit";

        return $db->fetchAll($sql, $params);
    }
}
```

### 6. AJAX Pattern (Async Operations)

**Why:** Non-blocking UI for long-running tasks (AI generation)

```javascript
// Frontend
async function bulkGenerateThumbnails(postIds) {
    const response = await fetch('ajax-bulk-operations.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({operation: 'generate_thumbnails', post_ids: postIds})
    });

    const result = await response.json();
    return result;
}

// Backend (ajax-bulk-operations.php)
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
// ... process ...
echo json_encode(['success' => true, 'processed' => 5]);
```

---

## Extension Guide

### Adding a New AI Provider

**Example:** Adding Cohere AI

**Step 1:** Create provider class

```php
// core/AI/CohereProvider.php
class CohereProvider extends AIProvider {
    private $api_key;
    private $endpoint = 'https://api.cohere.ai/v1/generate';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function generate($prompt, $options = []) {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'prompt' => $prompt,
            'model' => $options['model'] ?? 'command',
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'temperature' => $options['temperature'] ?? 0.7
        ]));

        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        return [
            'content' => $result['generations'][0]['text'],
            'tokens' => $result['meta']['billed_units']['output_tokens'],
            'cost' => $this->estimateCost(['tokens' => $result['meta']['billed_units']['output_tokens']])
        ];
    }

    public function validateApiKey() {
        return !empty($this->api_key);
    }

    public function getName() {
        return 'cohere';
    }

    public function estimateCost($options) {
        $tokens = $options['tokens'] ?? 1000;
        return ($tokens / 1000) * 0.001; // $1 per 1M tokens
    }
}
```

**Step 2:** Update ContentGenerator

```php
// core/AI/ContentGenerator.php
private function initializeProvider() {
    if ($this->preferredProvider === 'auto') {
        // Add Cohere to priority chain
        if (!empty(COHERE_API_KEY)) {
            return new CohereProvider(COHERE_API_KEY);
        }
        // ... existing providers
    }

    if ($this->preferredProvider === 'cohere') {
        return new CohereProvider(COHERE_API_KEY);
    }
}
```

**Step 3:** Update config.php

```php
// config.php
define('COHERE_API_KEY', '');
```

**Step 4:** Update admin settings UI

```php
// admin/settings.php
<input type="text" name="cohere_api_key" value="<?= COHERE_API_KEY ?>">

<select name="content_ai_provider">
    <option value="cohere">Cohere (Cheapest)</option>
    <!-- ... existing options -->
</select>
```

### Creating a Custom Theme

**Step 1:** Create theme directory

```bash
mkdir themes/mytheme
```

**Step 2:** Create required templates

```php
// themes/mytheme/header.php
<!DOCTYPE html>
<html>
<head>
    <title><?= $siteSettings['site_name'] ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/themes/mytheme/style.css">
</head>
<body>
    <header>
        <h1><?= $siteSettings['site_name'] ?></h1>
        <?php
        $menu = Menu::getByLocation('primary');
        Menu::render($menu->id);
        ?>
    </header>
    <main>

// themes/mytheme/footer.php
    </main>
    <footer>
        <p>&copy; <?= date('Y') ?> <?= $siteSettings['site_name'] ?></p>
    </footer>
</body>
</html>

// themes/mytheme/index.php
<?php include 'header.php'; ?>
<div class="posts">
    <?php foreach ($posts as $post): ?>
        <article>
            <h2><a href="<?= SITE_URL ?>/post/<?= $post->slug ?>"><?= $post->title ?></a></h2>
            <p><?= $post->excerpt ?></p>
        </article>
    <?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>

// themes/mytheme/single.php
<?php include 'header.php'; ?>
<article>
    <h1><?= $content->title ?></h1>
    <?php if ($content->featured_image): ?>
        <img src="<?= $content->featured_image ?>" alt="<?= $content->title ?>">
    <?php endif; ?>
    <div class="content"><?= $content->content ?></div>
</article>
<?php include 'footer.php'; ?>

// themes/mytheme/style.css
body { font-family: Arial, sans-serif; }
header { background: #333; color: white; padding: 20px; }
/* ... */
```

**Step 3:** Activate theme

```php
// config.php
define('CURRENT_THEME', 'mytheme');
```

### Adding Bulk Operations

**Example:** Bulk translate posts

**Step 1:** Add option to admin/posts.php

```php
// admin/posts.php
<select name="bulk_action" id="bulkAction">
    <!-- ... existing options -->
    <optgroup label="AI Operations">
        <option value="translate">Translate to Spanish</option>
    </optgroup>
</select>
```

**Step 2:** Handle in ajax-bulk-operations.php

```php
// admin/ajax-bulk-operations.php
case 'translate':
    $targetLanguage = 'es'; // Spanish

    foreach ($postIds as $postId) {
        $post = Post::find($postId);

        $contentGen = new ContentGenerator('auto');
        $result = $contentGen->generate(
            "Translate this blog post to Spanish:\n\n" .
            "Title: {$post->title}\n\n" .
            "Content: {$post->content}\n\n" .
            "Return JSON: {\"title\": \"...\", \"content\": \"...\"}",
            ['format' => 'json']
        );

        $translated = json_decode($result['content'], true);

        // Create new post with Spanish content
        $newPost = new Post();
        $newPost->title = $translated['title'];
        $newPost->slug = $post->slug . '-es';
        $newPost->content = $translated['content'];
        $newPost->status = 'draft';
        $newPost->save();
    }
    break;
```

---

## Development Roadmap

### Version 1.0 (COMPLETE)

✅ **Core CMS Features**
- Posts & Pages management
- Categories & Tags
- Menu builder (drag & drop)
- Media library
- SEO optimization (23+ fields)

✅ **AI Integration**
- Multi-provider architecture (OpenAI, Claude, Gemini, Unsplash)
- Image generation (DALL-E 3, Unsplash, PHP GD)
- Content generation
- SEO auto-generation

✅ **Admin Panel**
- Settings UI (AI provider configuration)
- Bulk operations (thumbnails, SEO)
- User authentication

✅ **Commercial Ready**
- Clean codebase
- Single database schema
- Documentation (README, ARCHITECTURE)
- Cost-effective defaults (Unsplash + Gemini = FREE)

### Version 1.1 (Planned)

🔜 **Enhanced AI Features**
- Auto-blogging campaigns (scheduled content generation)
- AI-powered internal linking
- Content rewriting/optimization
- Automatic FAQ generation

🔜 **Performance**
- Database query caching
- CDN integration for images
- Lazy loading for admin panel
- Redis/Memcached support

🔜 **Multi-Site**
- Satellite site network
- Cross-site internal linking
- Centralized admin panel
- White-label capabilities

### Version 2.0 (Future)

🔮 **Advanced Features**
- Affiliate integration (Amazon, ShareASale)
- A/B testing for headlines
- Analytics dashboard
- Custom fields for posts
- Advanced theme customization UI

🔮 **API & Integrations**
- REST API for headless CMS
- Webhook support
- Zapier integration
- WordPress import/export

🔮 **Enterprise**
- Multi-user collaboration
- Role-based permissions (admin, editor, contributor)
- Revision history
- Scheduled publishing queue
- Backup/restore system

---

## Best Practices

### Security

1. **Always use prepared statements**
   ```php
   // Good
   $db->query("SELECT * FROM posts WHERE id = ?", [$id]);

   // Bad
   $db->query("SELECT * FROM posts WHERE id = $id");
   ```

2. **Validate and sanitize input**
   ```php
   $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug']));
   $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
   ```

3. **Use CSRF tokens**
   ```php
   // Generate
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

   // Validate
   if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
       die('Invalid token');
   }
   ```

### Performance

1. **Cache expensive queries**
   ```php
   $cacheKey = "posts_published_page_{$page}";
   $posts = Cache::get($cacheKey);

   if (!$posts) {
       $posts = Post::all(['status' => 'published']);
       Cache::set($cacheKey, $posts, 3600); // 1 hour
   }
   ```

2. **Use indexes on database queries**
   ```sql
   CREATE INDEX idx_posts_status ON posts(status);
   CREATE INDEX idx_posts_published ON posts(published_at);
   ```

3. **Paginate results**
   ```php
   $posts = Post::all([], 'published_at DESC', 10); // LIMIT 10
   ```

### Code Quality

1. **Follow naming conventions**
   - Classes: `PascalCase` (e.g., `ImageGenerator`)
   - Methods: `camelCase` (e.g., `generateThumbnail`)
   - Database tables: `snake_case` (e.g., `menu_items`)

2. **Comment complex logic**
   ```php
   // Extract search query from title for Unsplash
   // "How to Build REST API" → "Build REST API" (remove filler words)
   private function extractSearchQuery($title) {
       // ...
   }
   ```

3. **Error handling**
   ```php
   try {
       $result = $provider->generate($prompt);
   } catch (Exception $e) {
       error_log('AI generation failed: ' . $e->getMessage());
       return ['error' => 'Generation failed', 'content' => null];
   }
   ```

---

## Troubleshooting

### Common Issues

**1. Images not generating**
- Check API keys in Settings panel
- Verify `content/uploads/` directory is writable (chmod 755)
- Check error logs: `tail -f /var/log/php_errors.log`

**2. Menu not saving**
- Check browser console for AJAX errors
- Verify `ajax-menu-save.php` permissions
- Check database connection

**3. SEO data not showing**
- Verify SEO fields exist in database (run schema.sql)
- Check template includes `<?php $seo->renderMetaTags($content); ?>`
- Clear browser cache

**4. AI generation expensive**
- Switch to Gemini for content ($0.0001/post)
- Switch to Unsplash for images (FREE)
- Update provider in Settings → AI Providers

### Debug Mode

Enable in `config.php`:

```php
define('DEBUG_MODE', true);
define('SITE_DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## Contact & Support

**Version:** 1.0
**Release Date:** 2025-10-24
**License:** Commercial

For technical questions, refer to this ARCHITECTURE.md and README.md.

---

*This document was generated by Claude Code to facilitate future AI-assisted development.*
