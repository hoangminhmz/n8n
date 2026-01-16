# 📦 DEPLOYMENT PACKAGE - Pages & Menu System

## 🎯 Files to Upload

### **REQUIRED FILES** (Must upload these):

```
admin/
  ├─ pages.php                    (NEW - 24KB)
  ├─ menus.php                    (NEW - 18KB)
  └─ includes/
      └─ header.php               (UPDATED - added Pages & Menus menu)

core/
  └─ Template.php                 (UPDATED - added page & menu functions)

themes/default/
  ├─ page.php                     (NEW - page template)
  └─ header.php                   (UPDATED - dynamic menu rendering)

index.php                         (UPDATED - page routing)

install/
  ├─ migrate-pages.sql            (NEW - pages table)
  └─ migrate-menus.sql            (NEW - menus tables)

check-installation.php            (NEW - diagnostic tool)
```

---

## 🚀 QUICK DEPLOYMENT STEPS

### Step 1: Upload Files

**Via FTP/cPanel File Manager:**
1. Download latest files from git branch
2. Upload to your server at: `/public_html/lite/` (or wherever your site is)
3. Replace existing files when prompted

### Step 2: Run Migrations

**Via phpMyAdmin:**
1. Open phpMyAdmin
2. Select database: `hoangmi5_lightblog`
3. Import `install/migrate-pages.sql`
4. Import `install/migrate-menus.sql`

### Step 3: Verify

1. Visit: `https://hoangminhmz.com/lite/check-installation.php`
2. Should see all ✅ green checkmarks
3. Login to admin
4. Should see **Pages** and **Menus** in sidebar

---

## 🔧 ALTERNATIVE: Manual File Upload Guide

If you can't use git, here's what to do:

### 1. Download Files from GitHub

Go to: `https://github.com/hoangminhmz/n8n/tree/claude/explore-source-code-011CUQRS3hVsmYk8z34DVBW2`

Download these files:

**New Files:**
- `admin/pages.php`
- `admin/menus.php`
- `themes/default/page.php`
- `install/migrate-pages.sql`
- `install/migrate-menus.sql`
- `check-installation.php`

**Updated Files:**
- `admin/includes/header.php`
- `core/Template.php`
- `themes/default/header.php`
- `index.php`

### 2. Upload via FTP

Using FileZilla/WinSCP/cPanel File Manager:
1. Connect to server
2. Navigate to `/lite/` directory
3. Upload all files to correct locations
4. Overwrite when asked

### 3. Set Permissions (if needed)

```bash
chmod 644 admin/pages.php
chmod 644 admin/menus.php
chmod 644 check-installation.php
```

---

## ✅ VERIFICATION CHECKLIST

After deployment:

- [ ] `check-installation.php` shows all green ✅
- [ ] Database tables exist: `pages`, `menus`, `menu_items`
- [ ] Can access `admin/pages.php` without 404
- [ ] Can access `admin/menus.php` without 404
- [ ] Admin sidebar shows "Pages" menu item
- [ ] Admin sidebar shows "Menus" menu item
- [ ] Can view page at `/lite/about`
- [ ] Can view page at `/lite/contact`

---

## 🆘 TROUBLESHOOTING

### Issue: Files uploaded but still not visible in admin

**Solution 1: Clear Cache**
```bash
rm -rf content/cache/*
```

**Solution 2: Hard Refresh Browser**
- Chrome/Firefox: Ctrl + Shift + R
- Or use Incognito mode

**Solution 3: Check File Paths**
Make sure files are in correct locations:
```
/public_html/lite/admin/pages.php
/public_html/lite/admin/menus.php
```

### Issue: PHP Errors

Check error logs:
```bash
tail -f error_log
# or
tail -f /var/log/apache2/error.log
```

Common issues:
- Missing semicolon
- Syntax error in updated files
- File permissions (should be 644)

---

## 📞 NEED HELP?

1. Run diagnostic: `check-installation.php`
2. Share the results
3. Check if database migrations ran successfully
4. Verify file permissions

---

## 🎯 QUICK CHECK COMMANDS

```bash
# Check files exist
ls -la admin/pages.php
ls -la admin/menus.php

# Check file sizes (should match)
# pages.php: ~24KB
# menus.php: ~18KB

# Check database tables
mysql -u user -p -e "USE hoangmi5_lightblog; SHOW TABLES LIKE 'pages';"
mysql -u user -p -e "USE hoangmi5_lightblog; SHOW TABLES LIKE 'menus';"
```

---

## 💡 PRO TIP

If you have SSH access, the fastest way:

```bash
cd /path/to/lite
git pull origin claude/explore-source-code-011CUQRS3hVsmYk8z34DVBW2
# Done in 2 seconds! 🚀
```

Otherwise, manual upload takes ~5-10 minutes.

---

**Good luck with deployment! 🚀**
