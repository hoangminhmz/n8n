<?php
/**
 * LightBlog CMS - Admin Header
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    $basePath = defined('BASE_PATH') ? BASE_PATH : '/';
    header('Location: ' . $basePath . 'admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get current user info
$db = Database::getInstance();
$currentUser = $db->queryOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$basePath = defined('BASE_PATH') ? BASE_PATH : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - LightBlog Admin</title>
    <link rel="stylesheet" href="<?= $basePath ?>admin/assets/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🚀 LightBlog</h1>
                <p>Admin Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= $basePath ?>admin/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section">Content</div>
                <a href="<?= $basePath ?>admin/posts.php" class="nav-item <?= $currentPage === 'posts' ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span>
                    <span>Posts</span>
                </a>
                <a href="<?= $basePath ?>admin/pages.php" class="nav-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
                    <span class="nav-icon">📄</span>
                    <span>Pages</span>
                </a>
                <a href="<?= $basePath ?>admin/categories.php" class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <span class="nav-icon">📁</span>
                    <span>Categories</span>
                </a>
                <a href="<?= $basePath ?>admin/media.php" class="nav-item <?= $currentPage === 'media' ? 'active' : '' ?>">
                    <span class="nav-icon">🖼️</span>
                    <span>Media</span>
                </a>
                <a href="<?= $basePath ?>admin/menus.php" class="nav-item <?= $currentPage === 'menus' ? 'active' : '' ?>">
                    <span class="nav-icon">🗂️</span>
                    <span>Menus</span>
                </a>

                <div class="nav-section">AI Auto-Blogging</div>
                <a href="<?= $basePath ?>admin/campaigns.php" class="nav-item <?= $currentPage === 'campaigns' ? 'active' : '' ?>">
                    <span class="nav-icon">🤖</span>
                    <span>Campaigns</span>
                </a>
                <a href="<?= $basePath ?>admin/queue.php" class="nav-item <?= $currentPage === 'queue' ? 'active' : '' ?>">
                    <span class="nav-icon">⏱️</span>
                    <span>Generation Queue</span>
                </a>

                <div class="nav-section">Monetization</div>
                <a href="<?= $basePath ?>admin/affiliates.php" class="nav-item <?= $currentPage === 'affiliates' ? 'active' : '' ?>">
                    <span class="nav-icon">💰</span>
                    <span>Affiliate Products</span>
                </a>

                <div class="nav-section">System</div>
                <a href="<?= $basePath ?>admin/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </a>
                <a href="<?= $basePath ?>admin/ai-prompts.php" class="nav-item <?= $currentPage === 'ai-prompts' ? 'active' : '' ?>">
                    <span class="nav-icon">🎨</span>
                    <span>AI Prompts</span>
                </a>
                <a href="<?= $basePath ?>admin/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span>Users</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($currentUser->username, 0, 1)) ?></div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($currentUser->username) ?></div>
                        <div class="user-role"><?= htmlspecialchars($currentUser->role) ?></div>
                    </div>
                </div>
                <a href="<?= $basePath ?>admin/logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="page-header-left">
                    <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                </div>
                <div class="page-header-right">
                    <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-outline">
                        👁️ View Site
                    </a>
                </div>
            </header>

            <div class="page-content">
