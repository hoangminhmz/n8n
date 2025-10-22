<?php
/**
 * LightBlog CMS - SEO Optimizer
 * Optimizes content for search engines
 */

class SEOOptimizer {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Optimize post content
     */
    public function optimize($content, $keywords = []) {
        // Add internal links
        $content = $this->addInternalLinks($content);

        // Optimize images
        $content = $this->optimizeImages($content);

        // Add schema markup
        $content = $this->addSchemaMarkup($content);

        return $content;
    }

    /**
     * Add internal links to content
     */
    private function addInternalLinks($content, $maxLinks = 3) {
        // Get random function based on database type
        $randomFunc = (defined('DB_TYPE') && DB_TYPE === 'mysql') ? 'RAND()' : 'RANDOM()';

        // Get related posts
        $posts = $this->db->query("
            SELECT title, slug FROM posts
            WHERE status = 'published'
            ORDER BY {$randomFunc}
            LIMIT ?
        ", [$maxLinks]);

        if (empty($posts)) {
            return $content;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $linksAdded = 0;

        foreach ($sentences as $i => &$sentence) {
            if ($linksAdded >= $maxLinks) break;

            foreach ($posts as $post) {
                if ($linksAdded >= $maxLinks) break;

                // Extract keywords from title
                $keywords = array_filter(
                    explode(' ', strtolower($post->title)),
                    function($word) { return strlen($word) > 4; }
                );

                foreach ($keywords as $keyword) {
                    if (stripos($sentence, $keyword) !== false) {
                        $url = SITE_URL . '/post/' . $post->slug;
                        $sentence = preg_replace(
                            '/\b' . preg_quote($keyword, '/') . '\b/i',
                            '<a href="' . $url . '">$0</a>',
                            $sentence,
                            1
                        );
                        $linksAdded++;
                        break 2;
                    }
                }
            }
        }

        return implode(' ', $sentences);
    }

    /**
     * Optimize images in content
     */
    private function optimizeImages($content) {
        // Add alt text, lazy loading, and dimensions
        $content = preg_replace_callback(
            '/<img([^>]*)>/i',
            function($matches) {
                $attrs = $matches[1];

                // Add alt text if missing
                if (!preg_match('/alt\s*=/i', $attrs)) {
                    // Extract filename for alt text
                    if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/', $attrs, $srcMatch)) {
                        $filename = basename($srcMatch[1]);
                        $alt = ucwords(str_replace(['-', '_', '.jpg', '.png', '.gif'], ' ', $filename));
                        $attrs .= ' alt="' . htmlspecialchars(trim($alt)) . '"';
                    }
                }

                // Add lazy loading
                if (!preg_match('/loading\s*=/i', $attrs)) {
                    $attrs .= ' loading="lazy"';
                }

                return '<img' . $attrs . '>';
            },
            $content
        );

        return $content;
    }

    /**
     * Add JSON-LD schema markup
     */
    private function addSchemaMarkup($content) {
        // Extract FAQ section if exists
        if (preg_match('/<h2[^>]*>.*?FAQ.*?<\/h2>(.*?)(?=<h2|$)/is', $content, $faqMatch)) {
            $faqContent = $faqMatch[1];

            // Extract Q&A pairs
            preg_match_all('/<h3[^>]*>(.*?)<\/h3>\s*<p>(.*?)<\/p>/is', $faqContent, $qa);

            if (!empty($qa[1])) {
                $faqSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => []
                ];

                foreach ($qa[1] as $i => $question) {
                    $faqSchema['mainEntity'][] = [
                        '@type' => 'Question',
                        'name' => strip_tags($question),
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($qa[2][$i] ?? '')
                        ]
                    ];
                }

                $schema = '<script type="application/ld+json">'
                    . json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    . '</script>';

                $content .= "\n" . $schema;
            }
        }

        return $content;
    }

    /**
     * Calculate readability score (Flesch Reading Ease)
     */
    public function calculateReadability($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($text);
        $syllables = $this->countSyllables($text);

        if (count($sentences) == 0 || $words == 0) {
            return 0;
        }

        $score = 206.835 - 1.015 * ($words / count($sentences)) - 84.6 * ($syllables / $words);

        return round(max(0, min(100, $score)), 1);
    }

    /**
     * Count syllables in text
     */
    private function countSyllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $syllables = 0;

        foreach ($words as $word) {
            $syllables += max(1, preg_match_all('/[aeiouy]+/', $word));
        }

        return $syllables;
    }

    /**
     * Check for keyword stuffing
     */
    public function checkKeywordStuffing($content, $keywords) {
        $text = strtolower(strip_tags($content));
        $wordCount = str_word_count($text);

        foreach ($keywords as $keyword) {
            $count = substr_count($text, strtolower($keyword));
            $density = ($count / $wordCount) * 100;

            if ($density > 3) { // More than 3% is considered stuffing
                return true;
            }
        }

        return false;
    }
}
