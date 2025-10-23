<?php
/**
 * LightBlog CMS - FAQ Schema Extractor
 * Extracts FAQ data and generates JSON-LD schema
 */

class FAQExtractor {

    /**
     * Extract FAQ data from content
     */
    public function extract($content) {
        $faqs = [];

        // Method 1: Look for FAQ section with H2/H3
        $faqSection = $this->extractFAQSection($content);
        if ($faqSection) {
            $faqs = $this->extractQAPairs($faqSection);
        }

        // Method 2: Look for accordion/collapsible patterns
        if (empty($faqs)) {
            $faqs = $this->extractAccordionFAQs($content);
        }

        // Method 3: Look for definition list pattern
        if (empty($faqs)) {
            $faqs = $this->extractDLFAQs($content);
        }

        return $faqs;
    }

    /**
     * Extract FAQ section from content
     */
    private function extractFAQSection($content) {
        // Look for FAQ heading
        if (preg_match('/<h2[^>]*>.*?(FAQ|Frequently Asked Questions|Q&A|Questions).*?<\/h2>(.*?)(?=<h2|$)/is', $content, $matches)) {
            return $matches[2];
        }

        return null;
    }

    /**
     * Extract Q&A pairs from FAQ section
     */
    private function extractQAPairs($faqSection) {
        $faqs = [];

        // Pattern: H3 question followed by P answer
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>\s*<p>(.*?)<\/p>/is', $faqSection, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $question = strip_tags($match[1]);
            $answer = strip_tags($match[2]);

            if (!empty($question) && !empty($answer)) {
                $faqs[] = [
                    'question' => trim($question),
                    'answer' => trim($answer)
                ];
            }
        }

        // Alternative pattern: Strong/Bold question followed by answer
        if (empty($faqs)) {
            preg_match_all('/<(?:strong|b)>(.*?)<\/(?:strong|b)>\s*<\/p>\s*<p>(.*?)<\/p>/is', $faqSection, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $question = strip_tags($match[1]);
                $answer = strip_tags($match[2]);

                if (!empty($question) && !empty($answer)) {
                    $faqs[] = [
                        'question' => trim($question),
                        'answer' => trim($answer)
                    ];
                }
            }
        }

        return $faqs;
    }

    /**
     * Extract FAQs from accordion/details pattern
     */
    private function extractAccordionFAQs($content) {
        $faqs = [];

        // HTML5 details/summary pattern
        preg_match_all('/<details[^>]*>.*?<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $question = strip_tags($match[1]);
            $answer = strip_tags($match[2]);

            if (!empty($question) && !empty($answer)) {
                $faqs[] = [
                    'question' => trim($question),
                    'answer' => trim($answer)
                ];
            }
        }

        return $faqs;
    }

    /**
     * Extract FAQs from definition list pattern
     */
    private function extractDLFAQs($content) {
        $faqs = [];

        // DL/DT/DD pattern
        preg_match_all('/<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/is', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $question = strip_tags($match[1]);
            $answer = strip_tags($match[2]);

            if (!empty($question) && !empty($answer)) {
                $faqs[] = [
                    'question' => trim($question),
                    'answer' => trim($answer)
                ];
            }
        }

        return $faqs;
    }

    /**
     * Generate FAQ JSON-LD schema
     */
    public function generateSchema($faqs) {
        if (empty($faqs)) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];

        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }

        return $schema;
    }

    /**
     * Detect schema type from content
     */
    public function detectSchemaType($content) {
        $content = strtolower($content);

        // HowTo detection
        if (preg_match('/step\s+\d+:|how\s+to|instructions:/i', $content)) {
            $stepCount = preg_match_all('/step\s+\d+/i', $content);
            if ($stepCount >= 3) {
                return 'HowTo';
            }
        }

        // Review detection
        if (preg_match('/\brating\b|\breview\b|★|⭐|pros\s+and\s+cons/i', $content)) {
            return 'Review';
        }

        // FAQ detection
        if (!empty($this->extract($content))) {
            return 'FAQPage';
        }

        // Default
        return 'Article';
    }

    /**
     * Generate Article schema
     */
    public function generateArticleSchema($post) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title ?? '',
            'description' => $post->meta_description ?? $post->excerpt ?? '',
            'datePublished' => $post->published_at ?? $post->created_at ?? date('Y-m-d H:i:s'),
            'dateModified' => $post->updated_at ?? $post->created_at ?? date('Y-m-d H:i:s')
        ];

        if (!empty($post->featured_image)) {
            $schema['image'] = $post->featured_image;
        }

        // Author (if available)
        if (!empty($post->author_name)) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $post->author_name
            ];
        }

        // Publisher (site info)
        if (defined('SITE_URL')) {
            $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => 'LightBlog',
                'url' => SITE_URL
            ];
        }

        return $schema;
    }
}
