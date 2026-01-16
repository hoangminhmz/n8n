# Backup & Restore Guide - LightBlog CMS

Complete guide to backing up and restoring your LightBlog CMS site.

---

## Overview

**Backup & Restore Feature:**
- Creates complete site backup (source code + database)
- Downloads as single ZIP file
- One-click restore from backup
- Automatic rollback on restore failure
- Built-in security (API keys removed from backup)

**Use Cases:**
- Before major updates
- Before installing new themes/plugins
- Daily/weekly automated backups
- Site migration to new server
- Disaster recovery

---

## Creating a Backup

### Method 1: Admin UI (Recommended)

1. Go to **Admin → Settings**
2. Scroll to **Backup & Restore** section
3. Click **"Create Backup"** button
4. Wait for backup creation (10-60 seconds depending on site size)
5. Click **"Download Backup"** when ready
6. Save ZIP file to safe location

**Backup Contains:**
- ✅ Complete source code
- ✅ Full database dump (all tables)
- ✅ Backup metadata (date, version, size)
- ✅ README with restore instructions
- ❌ API keys (removed for security)
- ❌ content/uploads folder (optional, can be large)

### Method 2: PHP Script (CLI)

For automated backups via cron:

```bash
php admin/backup.php --action=create_backup --output=/backups/
```

**Cron Example (Daily Backup at 2 AM):**
```bash
0 2 * * * php /path/to/lightblog/admin/backup.php --action=create_backup --output=/backups/ >> /var/log/backup.log 2>&1
```

---

## Restoring from Backup

### Step-by-Step Process

1. Go to **Admin → Settings**
2. Scroll to **Backup & Restore** section
3. Click **"Restore from Backup"**
4. Select your backup ZIP file
5. Check **"I understand this will overwrite all current data"**
6. Click **"Restore from Backup"**
7. Confirm the warning dialog
8. Wait for restore completion (30-120 seconds)
9. Re-enter API keys in Settings (they were removed for security)
10. Click **"Reload Page"**

**Important Notes:**
- ⚠️ Restore will overwrite ALL current data
- ✅ Automatic rollback if restore fails
- ✅ Database backup created before restore (for safety)
- ⚠️ You must re-enter API keys after restore

---

## What's Included in Backup

### Directory Structure

```
lightblog_backup_2025-10-24_12-00-00.zip
├── README.txt                    # Restore instructions
├── backup_info.json              # Metadata
├── database.sql                  # Full database dump
└── source/                       # Source code
    ├── admin/
    ├── core/
    ├── themes/
    ├── install/
    ├── index.php
    └── config.php                # API keys sanitized
```

### Database Tables Included

All tables are backed up:
- posts, pages, categories, tags
- menus, menu_items
- users, settings
- campaigns, ai_queue, ai_usage
- media

### Files Excluded

For size optimization:
- `content/uploads/` - Large media files (backup separately if needed)
- `.git/` - Version control history
- `node_modules/` - Development dependencies
- `vendor/` - PHP dependencies (can be regenerated)
- `.env` - Environment file
- `error_log` - Log files

---

## Backup File Details

### Metadata (backup_info.json)

```json
{
    "version": "1.0",
    "date": "2025-10-24 12:00:00",
    "php_version": "7.4.33",
    "site_name": "My Blog",
    "database_size": 1048576,
    "backup_type": "full"
}
```

### Database Dump (database.sql)

```sql
-- LightBlog CMS Database Backup
-- Date: 2025-10-24 12:00:00

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (...);
INSERT INTO `posts` VALUES (...);

-- ... all tables ...

SET FOREIGN_KEY_CHECKS=1;
```

---

## Security Considerations

### API Keys Sanitized

**Before Backup (config.php):**
```php
define('OPENAI_API_KEY', 'sk-proj-abc123def456...');
define('CLAUDE_API_KEY', 'sk-ant-xyz789...');
define('GEMINI_API_KEY', 'AIzaSyDI3GL...');
```

**In Backup (config.php):**
```php
define('OPENAI_API_KEY', 'YOUR_API_KEY_HERE');
define('CLAUDE_API_KEY', 'YOUR_API_KEY_HERE');
define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');
```

**Why?**
- Prevents accidental key exposure
- Safe to share backup with developers
- Safe to store in cloud storage
- No risk if backup is lost/stolen

### Database Credentials

Database credentials are also sanitized:

**Before:**
```php
define('DB_USER', 'root');
define('DB_PASS', 'secretpassword');
```

**After:**
```php
define('DB_USER', 'YOUR_DB_CREDENTIALS');
define('DB_PASS', 'YOUR_DB_CREDENTIALS');
```

---

## Backup Size Estimation

| Site Size | Database | Source Code | Total Backup |
|-----------|----------|-------------|--------------|
| Small (10 posts) | 500 KB | 2 MB | ~3 MB |
| Medium (100 posts) | 2 MB | 2 MB | ~5 MB |
| Large (1000 posts) | 10 MB | 2 MB | ~15 MB |
| Enterprise (10K posts) | 50 MB | 2 MB | ~60 MB |

**Note:** Excludes `content/uploads/` folder

**With Uploads:**
- Add 50-500 MB depending on image count
- Recommend separate backup for uploads

---

## Restore Process Details

### What Happens During Restore

1. **Validation**
   - Check ZIP file integrity
   - Validate backup structure
   - Check backup version compatibility

2. **Safety Backup**
   - Create rollback point (current database)
   - Store in temporary location
   - Used if restore fails

3. **Database Restore**
   - Drop existing tables
   - Recreate tables from backup
   - Import all data
   - Restore foreign key constraints

4. **Source Code Restore**
   - Extract source files
   - Skip config.php (keep current credentials)
   - Replace all other files
   - Maintain file permissions

5. **Cleanup**
   - Remove temporary files
   - Clear cache
   - Log restore activity

### Automatic Rollback

If restore fails at any step:
- Database is rolled back to pre-restore state
- Source files remain unchanged
- Error message displayed
- User notified of rollback

---

## Best Practices

### Regular Backups

**Recommended Schedule:**
- **Daily:** Automated cron backup
- **Before Updates:** Manual backup before any major change
- **Weekly:** Store offsite (cloud storage, external drive)
- **Monthly:** Verify backup integrity by test restore

### Backup Storage

**Local Storage:**
```bash
/backups/
├── daily/
│   ├── lightblog_backup_2025-10-24.zip
│   ├── lightblog_backup_2025-10-23.zip
│   └── lightblog_backup_2025-10-22.zip
├── weekly/
│   └── lightblog_backup_week43.zip
└── monthly/
    └── lightblog_backup_oct2025.zip
```

**Cloud Storage:**
- Google Drive
- Dropbox
- AWS S3
- Backblaze B2

**Retention Policy:**
- Keep daily backups for 7 days
- Keep weekly backups for 4 weeks
- Keep monthly backups for 12 months

### Backup Rotation Script

```bash
#!/bin/bash
# Rotate backups (keep last 7 days)

BACKUP_DIR="/backups/daily"
DAYS_TO_KEEP=7

# Create new backup
php /var/www/lightblog/admin/backup.php --action=create_backup --output=$BACKUP_DIR

# Delete old backups
find $BACKUP_DIR -name "lightblog_backup_*.zip" -mtime +$DAYS_TO_KEEP -delete

echo "Backup completed and old backups cleaned"
```

---

## Troubleshooting

### Issue: Backup Creation Fails

**Error:** "Failed to create backup directory"

**Solution:**
```bash
# Check permissions
ls -la /tmp/

# Ensure PHP can write to temp directory
sudo chown www-data:www-data /tmp/
sudo chmod 755 /tmp/
```

---

### Issue: Backup Download Fails

**Error:** "Backup file not found"

**Possible Causes:**
- Session expired
- Temp file cleaned by system
- Insufficient disk space

**Solution:**
1. Create new backup
2. Download immediately
3. Check disk space: `df -h`

---

### Issue: Backup Too Large

**Error:** "File size exceeds 500MB limit"

**Solution:**

**Option 1:** Exclude uploads folder
```php
// admin/backup.php - Already excluded by default
$excludePaths = [
    'content/uploads',  // Exclude large files
];
```

**Option 2:** Increase PHP limits
```ini
; php.ini
upload_max_filesize = 1000M
post_max_size = 1000M
memory_limit = 512M
max_execution_time = 300
```

**Option 3:** Backup uploads separately
```bash
# Manual uploads backup
tar -czf uploads_backup.tar.gz content/uploads/
```

---

### Issue: Restore Fails - Database Error

**Error:** "Failed to import database"

**Possible Causes:**
- SQL syntax error in backup
- Insufficient database permissions
- MySQL version mismatch

**Solution:**
1. Check error log: `tail -f /var/log/php_errors.log`
2. Verify database user has full permissions
3. Try manual restore:
```bash
mysql -u root -p database_name < database.sql
```

---

### Issue: Restore Fails - Source Code Error

**Error:** "Failed to extract ZIP"

**Solution:**
```bash
# Check ZIP integrity
unzip -t backup_file.zip

# Manual extraction
unzip backup_file.zip -d /tmp/restore_test/
```

---

### Issue: After Restore - API Keys Missing

**Expected Behavior:** API keys are removed for security

**Solution:**
1. Go to Admin → Settings
2. Re-enter all API keys:
   - OpenAI API Key
   - Claude API Key
   - Gemini API Key
   - Unsplash API Key
3. Click "Save Settings"

---

## Advanced Usage

### Backup Only Database

```php
// Create custom backup script
<?php
require_once '../autoload.php';
require_once '../admin/backup.php';

$dbFile = '/backups/database_only_' . date('Y-m-d') . '.sql';
exportDatabase($dbFile);
echo "Database backed up to: $dbFile\n";
?>
```

### Backup Specific Tables

```php
// Modify exportDatabase() function
function exportDatabaseTables($outputFile, $tables) {
    $db = Database::getInstance();
    $sql = "-- Partial Database Backup\n";

    foreach ($tables as $table) {
        // Export only specified tables
        // ... same logic as exportDatabase()
    }

    file_put_contents($outputFile, $sql);
}

// Usage
$tables = ['posts', 'pages', 'categories'];
exportDatabaseTables('/backups/content_only.sql', $tables);
```

### Scheduled Automated Backups

**Using Cron (Linux):**
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/bin/php /var/www/lightblog/admin/backup.php --action=create_backup --output=/backups/

# Add weekly backup on Sunday at 3 AM
0 3 * * 0 /usr/bin/php /var/www/lightblog/admin/backup.php --action=create_backup --output=/backups/weekly/
```

**Using Windows Task Scheduler:**
```
Program: C:\php\php.exe
Arguments: C:\xampp\htdocs\lightblog\admin\backup.php --action=create_backup
Start in: C:\xampp\htdocs\lightblog\
Trigger: Daily at 2:00 AM
```

---

## Migration to New Server

### Step-by-Step Migration

**On Old Server:**
1. Create backup
2. Download ZIP file
3. Export content/uploads separately (if large)

**On New Server:**
1. Install fresh LightBlog CMS
2. Configure database credentials in config.php
3. Go to Admin → Settings → Backup & Restore
4. Upload and restore backup ZIP
5. Re-enter API keys
6. Upload content/uploads separately (if needed)
7. Update SITE_URL in config.php
8. Test site thoroughly

---

## Commercial Features

### For Reselling LightBlog CMS

**White-Label Backup:**
```php
// config.php - Customize backup branding
define('BACKUP_BRAND_NAME', 'Your Company Backup');
define('BACKUP_SUPPORT_EMAIL', 'support@yourcompany.com');
```

**Backup Limits by Tier:**
```php
// Implement usage limits
$backupLimits = [
    'free' => 1,        // 1 backup per month
    'basic' => 10,      // 10 backups per month
    'pro' => 100,       // 100 backups per month
    'enterprise' => -1  // Unlimited
];
```

**Cloud Backup Integration:**
```php
// Auto-upload to cloud storage
function uploadToCloud($backupFile) {
    // AWS S3
    $s3 = new Aws\S3\S3Client([...]);
    $s3->putObject([
        'Bucket' => 'customer-backups',
        'Key' => basename($backupFile),
        'SourceFile' => $backupFile
    ]);
}
```

---

## Summary

**Backup & Restore is essential for:**
- ✅ Disaster recovery
- ✅ Site migration
- ✅ Version control
- ✅ Peace of mind

**Key Features:**
- ✅ One-click backup creation
- ✅ Complete site backup (code + database)
- ✅ Automatic security sanitization
- ✅ One-click restore
- ✅ Automatic rollback on failure
- ✅ Built-in for all LightBlog installations

**Recommended Setup:**
- Daily automated backups via cron
- Store backups offsite (cloud storage)
- Test restore process monthly
- Keep multiple backup versions

**Security:**
- API keys removed from backups
- Database credentials sanitized
- Safe to store in cloud
- Safe to share with developers

---

**Version:** 1.0
**Last Updated:** 2025-10-24
