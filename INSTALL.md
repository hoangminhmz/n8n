# LightBlog CMS - Installation Guide

## Quick Installation via Web Installer (Recommended)

LightBlog CMS includes an automatic web-based installation wizard that sets up everything for you.

### Step 1: Upload Files

Upload all LightBlog CMS files to your web server via FTP, cPanel File Manager, or any other method.

### Step 2: Access the Installer

Open your browser and navigate to:

```
http://yourdomain.com/install/
```

If `config.php` doesn't exist, the system will automatically redirect you to the installer.

### Step 3: Follow the Wizard

The installation wizard will guide you through 4 simple steps:

#### Step 1: Database Configuration
- Enter your MySQL/MariaDB credentials
- The installer will automatically create the database if it doesn't exist
- All tables and columns will be created with the complete schema

#### Step 2: Admin Account
- Create your administrator username and password
- This account will have full access to the admin panel

#### Step 3: AI Provider Configuration (Optional)
- Add your API keys for:
  - OpenAI (GPT-4, DALL-E)
  - Claude (Anthropic)
  - Google Gemini (Free tier available)
  - Unsplash (FREE stock photos)
- You can skip this step and add keys later in admin settings

#### Step 4: Complete
- Installation is complete!
- The system will create a `config.php` file with all your settings

### Step 4: Security

After installation, **DELETE or RENAME** the `/install` directory for security:

```
/install  →  /install_backup  (or delete it)
```

### Step 5: Login

Access your admin panel at:

```
http://yourdomain.com/admin/
```

## What Gets Installed

The web installer creates a **complete database structure** with:

### Core Tables
- ✅ **posts** (65 columns) - Including all SEO fields:
  - focus_keyword, canonical_url, meta_robots
  - og_title, og_description, og_image
  - twitter_title, twitter_description, twitter_image
  - schema_type, faq_data
  - readability_score, word_count, reading_time
  - seo_score, last_seo_check
  - And many more...

- ✅ **categories** - With icon and display_in_menu columns
- ✅ **tags** - Content tagging system
- ✅ **users** - Multi-user support with roles
- ✅ **pages** - Advanced page management system
- ✅ **menus** & **menu_items** - Custom menu builder
- ✅ **campaigns** - AI auto-blogging campaigns
- ✅ **ai_queue** - Automated content generation queue
- ✅ **affiliate_products** - Monetization system
- ✅ **media** - Media library
- ✅ **settings** - System configuration

### Default Data
- Default "Uncategorized" category
- Primary and Footer menus
- Sample pages (About, Contact)

## Manual Installation (Advanced)

If you prefer manual installation:

### 1. Create Database

```sql
CREATE DATABASE lightblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Import Schema

Import the complete schema file:

```bash
mysql -u username -p lightblog < install/schema.sql
```

### 3. Create config.php

Copy and edit the configuration:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lightblog');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

define('SITE_URL', 'http://yourdomain.com');
define('SITE_PATH', __DIR__);
define('BASE_PATH', '/');

// AI Provider API Keys (optional)
define('OPENAI_API_KEY', '');
define('CLAUDE_API_KEY', '');
define('GEMINI_API_KEY', '');
define('UNSPLASH_API_KEY', '');

define('DEFAULT_TEXT_PROVIDER', 'auto');
define('DEFAULT_IMAGE_PROVIDER', 'auto');
define('DEFAULT_TEXT_MODEL', 'gpt-4o-mini');

spl_autoload_register(function($class) {
    $file = SITE_PATH . '/core/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
```

### 4. Create Admin User

Run this SQL to create your admin account:

```sql
INSERT INTO users (username, email, password, role, created_at)
VALUES ('admin', 'admin@example.com', '$2y$10$...', 'admin', NOW());
```

(Replace password with `password_hash('yourpassword', PASSWORD_BCRYPT)`)

## System Requirements

- **PHP:** 7.4 or higher (8.0+ recommended)
- **MySQL/MariaDB:** 5.7+ / 10.2+
- **PHP Extensions:**
  - PDO with MySQL driver
  - cURL (for AI API calls)
  - GD or Imagick (for image processing)
  - JSON
  - mbstring
  - ZIP (for backup/restore)

## Post-Installation Configuration

After installation, configure these settings in the admin panel:

### 1. General Settings
- Site title and tagline
- Timezone
- Posts per page

### 2. AI Provider Settings
- Choose default text provider (OpenAI, Claude, Gemini)
- Choose default image provider (DALL-E, Unsplash, GD)
- Add/update API keys

### 3. SEO Settings
- Default meta description
- Social media links
- Analytics tracking codes

### 4. Permalinks
- Choose URL structure
- Configure .htaccess for pretty URLs

## Troubleshooting

### Installation Redirects to 404
- Make sure the `/install` directory exists
- Check file permissions (755 for directories, 644 for files)

### Database Connection Failed
- Verify database credentials
- Ensure MySQL server is running
- Check if your hosting allows remote database connections

### Cannot Write config.php
- Check write permissions on the root directory
- Try setting permissions to 755 or 777 temporarily
- Create config.php manually if needed

### Missing Columns Error After Installation
If you see "Unknown column 'focus_keyword'" errors:
- This should NOT happen with the web installer
- The schema.sql includes ALL columns from the start
- If it happens, use the migration tool at `/admin/database-migrate.php`

## Upgrading from Previous Versions

If you're upgrading from an older version that's missing database columns:

1. **Backup first:** Use Admin → Settings → Backup & Restore
2. **Run migration:** Access `/admin/database-check.php`
3. **Fix structure:** Click "Run Migration Tool"
4. **Verify:** Check that all features work correctly

The migration tool will safely add missing columns without affecting existing data.

## Free Tier Setup

Want to run LightBlog CMS with minimal costs?

### Recommended Configuration:
- **Text Generation:** Google Gemini (Free tier: 15 requests/minute)
- **Image Generation:** Unsplash (Free: 500 requests/hour)
- **Total Cost:** ~$0 per month for moderate usage

### Setup Steps:
1. Get free Gemini API key: https://makersuite.google.com/app/apikey
2. Get free Unsplash API key: https://unsplash.com/developers
3. Add both keys in Admin → Settings
4. Set default providers to "Gemini" and "Unsplash"

## Support

For issues or questions:
- Check the documentation in `/docs` directory
- Review ARCHITECTURE.md for technical details
- Use the database migration tools if needed
- Refer to BACKUP-RESTORE.md for backup procedures

---

**Version:** 1.0
**Last Updated:** October 2024
