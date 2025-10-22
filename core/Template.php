<?php
/**
 * LightBlog CMS - Template Engine
 * Template helper functions for themes
 */

class Template {
    private static $current_post;
    private static $db;
    private static $cache;

    /**
     * Initialize template system
     */
    public static function init() {
        self::$db = Database::getInstance();
        self::$cache = new Cache();
    }

    /**
     * Set current post
     * @param object $post Post object
     */
    public static function setCurrentPost($post) {
        self::$current_post = $post;
    }

    /**
     * Get current post
     * @return object|null
     */
    public static function getCurrentPost() {
        return self::$current_post;
    }

    // ========== Post Functions ==========

    public static function the_title() {
        if (!self::$current_post) return '';
        return htmlspecialchars(self::$current_post->title ?? '');
    }

    public static function the_content() {
        if (!self::$current_post) return '';
        return self::$current_post->content ?? '';
    }

    public static function the_excerpt($length = 160) {
        if (!self::$current_post) return '';
        $text = strip_tags(self::$current_post->excerpt ?: self::$current_post->content ?? '');
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }
        return $text;
    }

    public static function the_permalink() {
        if (!self::$current_post) {
            return SITE_URL;
        }
        $slug = self::$current_post->slug ?? '';
        return SITE_URL . '/post/' . $slug;
    }

    public static function featured_image_url($size = 'full') {
        return self::$current_post->featured_image ?? '';
    }

    public static function has_featured_image() {
        return !empty(self::$current_post->featured_image);
    }

    public static function get_date($format = 'Y-m-d') {
        $date = self::$current_post->published_at ?? self::$current_post->created_at ?? '';
        return $date ? date($format, strtotime($date)) : '';
    }

    public static function get_modified_date($format = 'Y-m-d') {
        $date = self::$current_post->updated_at ?? '';
        return $date ? date($format, strtotime($date)) : self::get_date($format);
    }

    public static function reading_time() {
        $content = self::$current_post->content ?? '';
        $wordCount = str_word_count(strip_tags($content));
        return ceil($wordCount / 200); // 200 words per minute
    }

    public static function view_count() {
        return number_format(self::$current_post->views ?? 0);
    }

    public static function author_name() {
        if (!isset(self::$current_post->author_id)) {
            return 'Unknown';
        }

        $author = self::$db->queryOne(
            "SELECT username FROM users WHERE id = ?",
            [self::$current_post->author_id]
        );

        return $author ? $author->username : 'Unknown';
    }

    // ========== Category Functions ==========

    public static function get_categories() {
        if (!isset(self::$current_post->id)) {
            return [];
        }

        return self::$db->query("
            SELECT c.* FROM categories c
            JOIN post_categories pc ON c.id = pc.category_id
            WHERE pc.post_id = ?
        ", [self::$current_post->id]);
    }

    public static function has_category() {
        return count(self::get_categories()) > 0;
    }

    public static function category_url($slug) {
        return SITE_URL . '/category/' . $slug;
    }

    // ========== Tag Functions ==========

    public static function get_tags() {
        if (!isset(self::$current_post->id)) {
            return [];
        }

        return self::$db->query("
            SELECT t.* FROM tags t
            JOIN post_tags pt ON t.id = pt.tag_id
            WHERE pt.post_id = ?
        ", [self::$current_post->id]);
    }

    public static function has_tags() {
        return count(self::get_tags()) > 0;
    }

    public static function tag_url($slug) {
        return SITE_URL . '/tag/' . $slug;
    }

    public static function post_url($slug) {
        return SITE_URL . '/post/' . $slug;
    }

    // ========== Related Posts ==========

    public static function get_related_posts($limit = 3) {
        if (!isset(self::$current_post->id)) {
            return [];
        }

        $cacheKey = 'related_posts_' . self::$current_post->id . '_' . $limit;

        return self::$cache->remember($cacheKey, function() use ($limit) {
            // Get keywords
            $keywords = json_decode(self::$current_post->keywords ?? '{}', true);
            $primaryKeywords = $keywords['primary'] ?? [];

            if (empty($primaryKeywords)) {
                // Fallback to same category
                return self::$db->query("
                    SELECT p.* FROM posts p
                    JOIN post_categories pc1 ON p.id = pc1.post_id
                    JOIN post_categories pc2 ON pc1.category_id = pc2.category_id
                    WHERE pc2.post_id = ?
                    AND p.id != ?
                    AND p.status = 'published'
                    GROUP BY p.id
                    ORDER BY p.views DESC
                    LIMIT ?
                ", [self::$current_post->id, self::$current_post->id, $limit]);
            }

            // Search by keywords
            $keywordString = implode(' ', $primaryKeywords);
            return self::$db->query("
                SELECT * FROM posts
                WHERE id != ?
                AND status = 'published'
                AND (content LIKE ? OR title LIKE ?)
                ORDER BY views DESC
                LIMIT ?
            ", [
                self::$current_post->id,
                "%{$keywordString}%",
                "%{$keywordString}%",
                $limit
            ]);
        }, 3600);
    }

    public static function has_related_posts() {
        return count(self::get_related_posts(1)) > 0;
    }

    // ========== Site Functions ==========

    public static function site_name() {
        return self::getSetting('site_name', 'My Blog');
    }

    public static function site_tagline() {
        return self::getSetting('site_tagline', 'Just another LightBlog site');
    }

    public static function site_logo() {
        return self::getSetting('site_logo', SITE_URL . '/assets/logo.png');
    }

    public static function meta_description() {
        if (!self::$current_post) {
            return htmlspecialchars(self::getSetting('site_tagline', 'Just another LightBlog site'));
        }
        $meta = self::$current_post->meta_description ?? '';
        return htmlspecialchars($meta ?: self::the_excerpt(155));
    }

    public static function seo_title() {
        if (!self::$current_post) {
            return htmlspecialchars(self::getSetting('site_name', 'My Blog'));
        }
        $title = self::$current_post->seo_title ?? self::$current_post->title ?? '';
        return htmlspecialchars($title);
    }

    private static function get_keywords() {
        if (!self::$current_post) {
            return '';
        }
        $keywords = json_decode(self::$current_post->keywords ?? '{}', true);
        return implode(', ', $keywords['primary'] ?? []);
    }

    // ========== Header/Footer ==========

    public static function get_header() {
        $theme = defined('CURRENT_THEME') ? CURRENT_THEME : 'default';
        $path = SITE_PATH . '/themes/' . $theme . '/header.php';
        if (file_exists($path)) {
            include $path;
        }
    }

    public static function get_footer() {
        $theme = defined('CURRENT_THEME') ? CURRENT_THEME : 'default';
        $path = SITE_PATH . '/themes/' . $theme . '/footer.php';
        if (file_exists($path)) {
            include $path;
        }
    }

    public static function wp_head() {
        $title = self::seo_title();
        $description = self::meta_description();
        $image = self::featured_image_url();
        $url = self::the_permalink();
        $keywords = self::get_keywords();

        echo '<!-- SEO Meta Tags -->' . "\n";
        echo '<meta name="description" content="' . $description . '">' . "\n";
        if ($keywords) {
            echo '<meta name="keywords" content="' . $keywords . '">' . "\n";
        }

        echo "\n<!-- Open Graph -->\n";
        echo '<meta property="og:title" content="' . $title . '">' . "\n";
        echo '<meta property="og:description" content="' . $description . '">' . "\n";
        if ($image) {
            echo '<meta property="og:image" content="' . $image . '">' . "\n";
        }
        echo '<meta property="og:url" content="' . $url . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";

        echo "\n<!-- Twitter Card -->\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
        echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
        if ($image) {
            echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
        }

        echo "\n<!-- Canonical -->\n";
        echo '<link rel="canonical" href="' . $url . '">' . "\n";
    }

    // ========== Helper Functions ==========

    public static function getSetting($key, $default = null) {
        $cacheKey = 'setting_' . $key;

        return self::$cache->remember($cacheKey, function() use ($key, $default) {
            $setting = self::$db->queryOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
            return $setting ? $setting->value : $default;
        }, 86400); // Cache for 24 hours
    }

    public static function asset_url($path) {
        $cdnUrl = defined('CDN_URL') ? CDN_URL : SITE_URL;
        return $cdnUrl . $path;
    }

    public static function theme_url($path = '') {
        $theme = defined('CURRENT_THEME') ? CURRENT_THEME : 'default';
        return SITE_URL . '/themes/' . $theme . $path;
    }

    public static function excerpt($text, $length = 100) {
        $text = strip_tags($text);
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }
        return $text;
    }
}

// ========== Global Template Functions ==========

function the_title() { return Template::the_title(); }
function the_content() { return Template::the_content(); }
function the_excerpt($length = 160) { return Template::the_excerpt($length); }
function the_permalink() { return Template::the_permalink(); }
function featured_image_url() { return Template::featured_image_url(); }
function has_featured_image() { return Template::has_featured_image(); }
function get_date($format = 'Y-m-d') { return Template::get_date($format); }
function get_modified_date($format = 'Y-m-d') { return Template::get_modified_date($format); }
function reading_time() { return Template::reading_time(); }
function view_count() { return Template::view_count(); }
function author_name() { return Template::author_name(); }
function get_categories() { return Template::get_categories(); }
function has_category() { return Template::has_category(); }
function category_url($slug) { return Template::category_url($slug); }
function get_tags() { return Template::get_tags(); }
function has_tags() { return Template::has_tags(); }
function tag_url($slug) { return Template::tag_url($slug); }
function post_url($slug) { return Template::post_url($slug); }
function get_related_posts($limit = 3) { return Template::get_related_posts($limit); }
function has_related_posts() { return Template::has_related_posts(); }
function site_name() { return Template::site_name(); }
function site_tagline() { return Template::site_tagline(); }
function site_logo() { return Template::site_logo(); }
function meta_description() { return Template::meta_description(); }
function seo_title() { return Template::seo_title(); }
function get_header() { Template::get_header(); }
function get_footer() { Template::get_footer(); }
function wp_head() { Template::wp_head(); }
function asset_url($path) { return Template::asset_url($path); }
function theme_url($path = '') { return Template::theme_url($path); }
function excerpt($text, $length = 100) { return Template::excerpt($text, $length); }
