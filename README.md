# LightBlog CMS v1.0 🚀

AI-Powered Content Management System - Commercial Edition

## Quick Start

```bash
# 1. Create database
mysql -u root -p
CREATE DATABASE lightblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 2. Import schema (includes all tables + sample data)
mysql -u root -p lightblog < install/schema.sql

# 3. Configure
cp config.sample.php config.php
nano config.php
# Update DB_NAME, DB_USER, DB_PASS

# 4. Access admin
http://yourdomain.com/admin/
Login: admin / password123
```

**✨ One command install:**
```bash
mysql -u root -p lightblog < install/schema.sql
```
All tables, migrations, and sample data are included!

## Features

✅ Posts & Pages Management
✅ Menu Builder (Drag & Drop)
✅ 3 Image Options: Unsplash (FREE) | OpenAI | PHP GD
✅ 3 AI Providers: Gemini | Claude | OpenAI
✅ Bulk Operations (Thumbnails + SEO)
✅ Backup & Restore (One-Click)
✅ Admin Settings UI
✅ SEO Optimization (23+ fields)
✅ Multi-Provider Architecture

## Cost Comparison

| Provider | Cost | Best For |
|----------|------|----------|
| Unsplash | FREE | Stock images |
| Gemini | $0.0001/post | Content (cheap) |
| OpenAI | $0.04/image | AI images |

Recommended: Unsplash + Gemini = FREE!

## Documentation

- `ARCHITECTURE.md` - Technical details
- `SETUP-OPENAI.md` - OpenAI setup guide
- `THUMBNAIL-GUIDE.md` - Image generation
- `BACKUP-RESTORE.md` - Backup & restore guide

## Version 1.0 Complete ✨

Ready for commercial distribution!
