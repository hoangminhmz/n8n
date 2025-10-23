<?php
/**
 * LightBlog CMS - Main Entry Point
 * Handles all frontend requests
 */

// Load configuration
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Redirect to installation if config doesn't exist
    if (file_exists(__DIR__ . '/install.php')) {
        header('Location: /install.php');
        exit;
    } else {
        die('Configuration file not found. Please copy config.sample.php to config.php and configure your settings.');
    }
}

// Load core classes
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Cache.php';
require_once SITE_PATH . '/core/Router.php';
require_once SITE_PATH . '/core/Template.php';

// Initialize
$db = Database::getInstance();
$cache = new Cache();
$router = new Router();
Template::init();

// Define routes
$router->add('#^/$#', function() use ($db, $cache) {
    // Homepage - show latest posts
    $posts = $cache->remember('homepage_posts', function() use ($db) {
        return $db->query("
            SELECT * FROM posts
            WHERE status = 'published'
            ORDER BY published_at DESC
            LIMIT 10
        ");
    }, 600);

    include SITE_PATH . '/themes/' . CURRENT_THEME . '/index.php';
});

$router->add('#^/post/([a-z0-9-]+)$#', function($slug) use ($db) {
    // Single post
    $post = $db->queryOne("SELECT * FROM posts WHERE slug = ? AND status = 'published'", [$slug]);

    if (!$post) {
        http_response_code(404);
        include SITE_PATH . '/themes/' . CURRENT_THEME . '/404.php';
        return;
    }

    // Increment view count
    $db->query("UPDATE posts SET views = views + 1 WHERE id = ?", [$post->id]);

    // Set current post for template functions
    Template::setCurrentPost($post);

    include SITE_PATH . '/themes/' . CURRENT_THEME . '/single.php';
});

$router->add('#^/category/([a-z0-9-]+)$#', function($slug) use ($db) {
    // Category archive
    $category = $db->queryOne("SELECT * FROM categories WHERE slug = ?", [$slug]);

    if (!$category) {
        http_response_code(404);
        include SITE_PATH . '/themes/' . CURRENT_THEME . '/404.php';
        return;
    }

    $posts = $db->query("
        SELECT p.* FROM posts p
        JOIN post_categories pc ON p.id = pc.post_id
        WHERE pc.category_id = ? AND p.status = 'published'
        ORDER BY p.published_at DESC
        LIMIT 20
    ", [$category->id]);

    include SITE_PATH . '/themes/' . CURRENT_THEME . '/archive.php';
});

$router->add('#^/tag/([a-z0-9-]+)$#', function($slug) use ($db) {
    // Tag archive
    $tag = $db->queryOne("SELECT * FROM tags WHERE slug = ?", [$slug]);

    if (!$tag) {
        http_response_code(404);
        include SITE_PATH . '/themes/' . CURRENT_THEME . '/404.php';
        return;
    }

    $posts = $db->query("
        SELECT p.* FROM posts p
        JOIN post_tags pt ON p.id = pt.post_id
        WHERE pt.tag_id = ? AND p.status = 'published'
        ORDER BY p.published_at DESC
        LIMIT 20
    ", [$tag->id]);

    include SITE_PATH . '/themes/' . CURRENT_THEME . '/archive.php';
});

// Generic slug route - checks pages first, then posts
// NOTE: This must be last so specific routes above are matched first
$router->add('#^/([a-z0-9-]+)$#', function($slug) use ($db) {
    // Try to find a page first (pages have priority over posts)
    $page = $db->queryOne("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$slug]);

    if ($page) {
        // Page found - increment view count
        $db->query("UPDATE pages SET views = views + 1 WHERE id = ?", [$page->id]);

        // Set current page for template functions
        Template::setCurrentPage($page);

        // Determine which template to use
        $templateFile = 'page.php';
        if ($page->template && $page->template !== 'default') {
            $customTemplate = SITE_PATH . '/themes/' . CURRENT_THEME . '/page-' . $page->template . '.php';
            if (file_exists($customTemplate)) {
                $templateFile = 'page-' . $page->template . '.php';
            }
        }

        include SITE_PATH . '/themes/' . CURRENT_THEME . '/' . $templateFile;
        return;
    }

    // No page found, try post (for backward compatibility with /post/slug URLs)
    $post = $db->queryOne("SELECT * FROM posts WHERE slug = ? AND status = 'published'", [$slug]);

    if ($post) {
        // Post found - increment view count
        $db->query("UPDATE posts SET views = views + 1 WHERE id = ?", [$post->id]);

        // Set current post for template functions
        Template::setCurrentPost($post);

        include SITE_PATH . '/themes/' . CURRENT_THEME . '/single.php';
        return;
    }

    // Neither page nor post found - 404
    http_response_code(404);
    include SITE_PATH . '/themes/' . CURRENT_THEME . '/404.php';
});

// Dispatch request
$uri = Router::getCurrentUri();
$router->dispatch($uri);
