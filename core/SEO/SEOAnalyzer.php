<?php
/**
 * LightBlog CMS - SEO Content Analyzer
 * Analyzes content for SEO quality and generates metrics
 */

class SEOAnalyzer {

    /**
     * Analyze post content and return all SEO metrics
     */
    public function analyze($post, $content) {
        $metrics = [
            'word_count' => $this->countWords($content),
            'reading_time' => $this->calculateReadingTime($content),
            'readability_score' => $this->calculateReadability($content),
            'internal_links_count' => $this->countLinks($content, 'internal'),
            'external_links_count' => $this->countLinks($content, 'external'),
            'images_count' => $this->countImages($content),
            'has_table_of_contents' => $this->hasTOC($content),
            'focus_keyword' => $post->focus_keyword ?? null,
            'seo_score' => 0
        ];

        // Calculate SEO score
        $metrics['seo_score'] = $this->calculateSEOScore($post, $content, $metrics);

        return $metrics;
    }

    /**
     * Count words in content
     */
    public function countWords($content) {
        $text = strip_tags($content);
        return str_word_count($text);
    }

    /**
     * Calculate reading time (minutes)
     */
    public function calculateReadingTime($content) {
        $wordCount = $this->countWords($content);
        return max(1, ceil($wordCount / 200)); // 200 words per minute
    }

    /**
     * Calculate readability score (Flesch Reading Ease)
     */
    public function calculateReadability($content) {
        $text = strip_tags($content);
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
     * Count links (internal or external)
     */
    public function countLinks($content, $type = 'all') {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/', $content, $matches);

        if ($type === 'all') {
            return count($matches[1]);
        }

        $count = 0;
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';

        foreach ($matches[1] as $url) {
            $isInternal = strpos($url, $siteUrl) !== false || strpos($url, '/') === 0;

            if ($type === 'internal' && $isInternal) {
                $count++;
            } elseif ($type === 'external' && !$isInternal) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count images in content
     */
    public function countImages($content) {
        return preg_match_all('/<img[^>]+>/', $content);
    }

    /**
     * Check if content has table of contents
     */
    public function hasTOC($content) {
        // Check for TOC markers or multiple H2/H3 headings
        if (preg_match('/<div[^>]*class=["\'][^"\']*table-of-contents/', $content)) {
            return 1;
        }

        $h2Count = preg_match_all('/<h2[^>]*>/', $content);
        return $h2Count >= 3 ? 1 : 0;
    }

    /**
     * Calculate overall SEO score (0-100)
     */
    public function calculateSEOScore($post, $content, $metrics) {
        $score = 0;
        $text = strtolower(strip_tags($content));
        $title = strtolower($post->title ?? '');
        $focusKeyword = strtolower($post->focus_keyword ?? '');

        // Has focus keyword (10 points)
        if (!empty($focusKeyword)) {
            $score += 10;

            // Keyword in title (10 points)
            if (strpos($title, $focusKeyword) !== false) {
                $score += 10;
            }

            // Keyword in first 100 words (10 points)
            $firstWords = implode(' ', array_slice(str_word_count($text, 1), 0, 100));
            if (strpos($firstWords, $focusKeyword) !== false) {
                $score += 10;
            }
        }

        // Meta description present (10 points)
        if (!empty($post->meta_description)) {
            $score += 10;
        }

        // Optimal word count 1500-2500 (10 points)
        if ($metrics['word_count'] >= 1500 && $metrics['word_count'] <= 2500) {
            $score += 10;
        } elseif ($metrics['word_count'] >= 1000) {
            $score += 5;
        }

        // Has internal links 2-5 (10 points)
        if ($metrics['internal_links_count'] >= 2 && $metrics['internal_links_count'] <= 5) {
            $score += 10;
        } elseif ($metrics['internal_links_count'] >= 1) {
            $score += 5;
        }

        // Has external links 1-3 (10 points)
        if ($metrics['external_links_count'] >= 1 && $metrics['external_links_count'] <= 3) {
            $score += 10;
        }

        // Has images (10 points)
        if ($metrics['images_count'] >= 3) {
            $score += 10;
        } elseif ($metrics['images_count'] >= 1) {
            $score += 5;
        }

        // Good readability score > 60 (10 points)
        if ($metrics['readability_score'] >= 60) {
            $score += 10;
        } elseif ($metrics['readability_score'] >= 40) {
            $score += 5;
        }

        // Has TOC for long content (10 points)
        if ($metrics['has_table_of_contents'] && $metrics['word_count'] >= 1500) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Get SEO score label and color
     */
    public function getScoreLabel($score) {
        if ($score >= 80) {
            return ['label' => 'Excellent', 'color' => 'success'];
        } elseif ($score >= 60) {
            return ['label' => 'Good', 'color' => 'info'];
        } elseif ($score >= 40) {
            return ['label' => 'Fair', 'color' => 'warning'];
        } else {
            return ['label' => 'Poor', 'color' => 'danger'];
        }
    }

    /**
     * Get SEO recommendations
     */
    public function getRecommendations($post, $content, $metrics) {
        $recommendations = [];
        $focusKeyword = $post->focus_keyword ?? '';

        if (empty($focusKeyword)) {
            $recommendations[] = '⚠️ Add a focus keyword';
        } else {
            if (strpos(strtolower($post->title ?? ''), strtolower($focusKeyword)) === false) {
                $recommendations[] = '⚠️ Include focus keyword in title';
            }
        }

        if (empty($post->meta_description)) {
            $recommendations[] = '⚠️ Add meta description (150-160 chars)';
        }

        if ($metrics['word_count'] < 1000) {
            $recommendations[] = '⚠️ Content too short (aim for 1500-2500 words)';
        }

        if ($metrics['internal_links_count'] < 2) {
            $recommendations[] = '⚠️ Add more internal links (2-5 recommended)';
        }

        if ($metrics['external_links_count'] < 1) {
            $recommendations[] = '⚠️ Add external authoritative links';
        }

        if ($metrics['images_count'] < 1) {
            $recommendations[] = '⚠️ Add images to improve engagement';
        }

        if ($metrics['readability_score'] < 60) {
            $recommendations[] = '⚠️ Improve readability (shorter sentences)';
        }

        if (!$metrics['has_table_of_contents'] && $metrics['word_count'] >= 1500) {
            $recommendations[] = '⚠️ Add table of contents for long article';
        }

        return $recommendations;
    }
}
