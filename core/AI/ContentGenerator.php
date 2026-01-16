<?php
/**
 * LightBlog CMS - Content Generator
 * Main content generation engine using AI providers
 */

class ContentGenerator {
    private $aiProvider;
    private $campaign;
    private $db;
    private $promptManager;

    /**
     * Constructor
     * @param object $campaign Campaign object
     */
    public function __construct($campaign) {
        $this->campaign = $campaign;
        $this->db = Database::getInstance();
        $this->initAIProvider();

        // Initialize PromptManager for customizable prompts
        require_once __DIR__ . '/PromptManager.php';
        $this->promptManager = new PromptManager();
    }

    /**
     * Initialize AI provider based on campaign settings
     */
    private function initAIProvider() {
        $provider = $this->campaign->ai_provider ?? 'openai';
        $model = $this->campaign->ai_model ?? 'gpt-4';

        switch ($provider) {
            case 'openai':
                require_once __DIR__ . '/OpenAIProvider.php';
                $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
                $this->aiProvider = new OpenAIProvider($apiKey, $model);
                break;

            case 'claude':
                require_once __DIR__ . '/ClaudeProvider.php';
                $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';
                $this->aiProvider = new ClaudeProvider($apiKey, $model);
                break;

            case 'gemini':
                require_once __DIR__ . '/GeminiProvider.php';
                $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
                $this->aiProvider = new GeminiProvider($apiKey, $model);
                break;

            default:
                throw new Exception('Unsupported AI provider: ' . $provider);
        }
    }

    /**
     * Generate a complete article with full SEO optimization
     * @param string $topic Article topic
     * @param array $keywords Keywords to include
     * @return array Article data
     */
    public function generateArticle($topic, $keywords) {
        // Step 1: Generate outline
        $outline = $this->generateOutline($topic, $keywords);

        // Step 2: Generate content
        $content = $this->generateContent($outline, $keywords);

        // Step 3: Add Table of Contents (if needed)
        require_once __DIR__ . '/../SEO/TOCGenerator.php';
        $tocGen = new TOCGenerator();
        $content = $tocGen->generate($content);

        // Step 4: Generate metadata
        $metadata = $this->generateMetadata($topic, $keywords);

        // Step 5: Generate featured image (placeholder for now)
        $featuredImage = $this->generateFeaturedImage($topic);

        // Step 6: Generate SEO fields - extract meaningful focus keyword
        $focusKeyword = $this->extractFocusKeyword($topic, $keywords);
        $canonicalUrl = SITE_URL . BASE_PATH . 'post/' . $this->generateSlug($metadata['title']);

        // Step 7: Extract FAQ data
        require_once __DIR__ . '/../SEO/FAQExtractor.php';
        $faqExtractor = new FAQExtractor();
        $faqs = $faqExtractor->extract($content);

        // If no FAQs found in content, try to use outline FAQs
        if (empty($faqs) && !empty($outline['faqs'])) {
            $faqs = [];
            foreach ($outline['faqs'] as $faq) {
                if (!empty($faq['question'])) {
                    $faqs[] = [
                        'question' => $faq['question'],
                        'answer' => $faq['answer_hint'] ?? 'See article for details.'
                    ];
                }
            }
        }

        $schemaType = $faqExtractor->detectSchemaType($content);

        // Step 8: Analyze content quality
        require_once __DIR__ . '/../SEO/SEOAnalyzer.php';
        $seoAnalyzer = new SEOAnalyzer();

        // Create temporary post object for analysis
        $tempPost = (object)[
            'title' => $metadata['title'],
            'meta_description' => $metadata['meta_description'],
            'focus_keyword' => $focusKeyword
        ];

        $seoMetrics = $seoAnalyzer->analyze($tempPost, $content);

        return [
            // Basic fields
            'title' => $metadata['title'],
            'slug' => $this->generateSlug($metadata['title']),
            'content' => $content,
            'excerpt' => $this->generateExcerpt($content),
            'featured_image' => $featuredImage,
            'keywords' => json_encode($keywords),

            // SEO Meta
            'seo_title' => $metadata['seo_title'],
            'meta_description' => $metadata['meta_description'],
            'focus_keyword' => $focusKeyword,
            'canonical_url' => $canonicalUrl,
            'meta_robots' => 'index,follow',

            // Open Graph
            'og_title' => $metadata['seo_title'],
            'og_description' => $metadata['meta_description'],
            'og_image' => $featuredImage,

            // Twitter Cards
            'twitter_title' => $metadata['seo_title'],
            'twitter_description' => $metadata['meta_description'],
            'twitter_image' => $featuredImage,

            // Schema & Structured Data
            'schema_type' => $schemaType,
            'faq_data' => !empty($faqs) ? json_encode($faqs) : null,

            // Content Quality Metrics
            'word_count' => $seoMetrics['word_count'],
            'reading_time' => $seoMetrics['reading_time'],
            'readability_score' => $seoMetrics['readability_score'],
            'internal_links_count' => $seoMetrics['internal_links_count'],
            'external_links_count' => $seoMetrics['external_links_count'],
            'images_count' => $seoMetrics['images_count'],
            'has_table_of_contents' => $seoMetrics['has_table_of_contents'],

            // SEO Score
            'seo_score' => $seoMetrics['seo_score'],
            'last_seo_check' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate article outline
     * @param string $topic Topic
     * @param array $keywords Keywords
     * @return array Outline
     */
    private function generateOutline($topic, $keywords) {
        $primaryKeywords = implode(', ', $keywords['primary'] ?? []);
        $lsiKeywords = implode(', ', $keywords['lsi'] ?? []);

        // Use customizable prompt from PromptManager
        $prompt = $this->promptManager->getPrompt('post_outline', [
            'topic' => $topic,
            'primary_keywords' => $primaryKeywords,
            'lsi_keywords' => $lsiKeywords,
            'word_count_min' => $this->campaign->word_count_min ?? 1500,
            'word_count_max' => $this->campaign->word_count_max ?? 2500,
            'niche' => $this->campaign->niche ?? 'General'
        ]);

        $result = $this->aiProvider->generate($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 1500,
            'campaign_id' => $this->campaign->id
        ]);

        // Parse JSON response
        $outline = json_decode($result['content'], true);

        if (!$outline) {
            // Fallback to basic outline if JSON parsing fails
            $outline = [
                'h1' => $topic,
                'sections' => [
                    ['h2' => 'Introduction', 'h3' => []],
                    ['h2' => 'Main Content', 'h3' => []],
                    ['h2' => 'Conclusion', 'h3' => []]
                ],
                'faqs' => []
            ];
        }

        return $outline;
    }

    /**
     * Generate article content from outline
     * @param array $outline Article outline
     * @param array $keywords Keywords
     * @return string HTML content
     */
    private function generateContent($outline, $keywords) {
        $tone = $this->campaign->tone ?? 'professional';
        $primaryKeywords = implode(', ', $keywords['primary'] ?? []);

        // Use customizable prompt from PromptManager
        $prompt = $this->promptManager->getPrompt('post_content', [
            'outline' => json_encode($outline, JSON_PRETTY_PRINT),
            'tone' => $tone,
            'primary_keywords' => $primaryKeywords,
            'niche' => $this->campaign->niche ?? 'General',
            'word_count' => $this->campaign->word_count_min ?? 2000
        ]);

        $result = $this->aiProvider->generate($prompt, [
            'temperature' => $this->campaign->ai_temperature ?? 0.7,
            'max_tokens' => 4000,
            'campaign_id' => $this->campaign->id,
            'system_prompt' => "You are an expert content writer specializing in {$this->campaign->niche}. Write engaging, informative content that ranks well in search engines."
        ]);

        // Clean up markdown code blocks if AI returns them
        $content = $result['content'];
        $content = preg_replace('/^```html\s*/i', '', $content);
        $content = preg_replace('/^```\s*/m', '', $content);
        $content = preg_replace('/\s*```$/s', '', $content);
        $content = trim($content);

        return $content;
    }

    /**
     * Generate metadata (title, description, etc.)
     * @param string $topic Topic
     * @param array $keywords Keywords
     * @return array Metadata
     */
    private function generateMetadata($topic, $keywords) {
        $primaryKeyword = $keywords['primary'][0] ?? $topic;

        // Use customizable prompt from PromptManager
        $prompt = $this->promptManager->getPrompt('post_title', [
            'topic' => $topic,
            'primary_keyword' => $primaryKeyword,
            'niche' => $this->campaign->niche ?? 'General',
            'tone' => $this->campaign->tone ?? 'professional',
            'year' => date('Y')
        ]);

        $result = $this->aiProvider->generate($prompt, [
            'temperature' => 0.9,
            'max_tokens' => 500,
            'campaign_id' => $this->campaign->id
        ]);

        // Clean markdown if present
        $jsonContent = $result['content'];
        $jsonContent = preg_replace('/^```json\s*/i', '', $jsonContent);
        $jsonContent = preg_replace('/^```\s*/m', '', $jsonContent);
        $jsonContent = preg_replace('/\s*```$/s', '', $jsonContent);
        $jsonContent = trim($jsonContent);

        $metadata = json_decode($jsonContent, true);

        if (!$metadata || empty($metadata['meta_description'])) {
            // Fallback - create description from topic
            $metadata = [
                'title' => $topic,
                'seo_title' => $topic,
                'meta_description' => "Discover everything you need to know about {$primaryKeyword}. Expert insights, tips, and comprehensive guide."
            ];
        }

        return $metadata;
    }

    /**
     * Extract focus keyword from topic
     * @param string $topic Topic
     * @param array $keywords Keywords array
     * @return string Focus keyword
     */
    private function extractFocusKeyword($topic, $keywords) {
        // Priority 1: Use primary keyword if available
        if (!empty($keywords['primary'][0])) {
            return $keywords['primary'][0];
        }

        // Priority 2: Extract main keyword from topic (2-4 words)
        // Remove common article prefixes
        $cleaned = preg_replace('/^(the|a|an|how to|guide to|best|top|what is|why|when|where)\s+/i', '', $topic);

        // Remove year patterns like "2025", "in 2024"
        $cleaned = preg_replace('/\s+(in\s+)?\d{4}(\s+|$)/i', ' ', $cleaned);

        // Remove phrases like "for optimal health and comfort"
        $cleaned = preg_replace('/\s+for\s+[^:]+$/i', '', $cleaned);

        // Get first 2-4 important words
        $words = preg_split('/\s+/', trim($cleaned));
        $words = array_filter($words, function($w) {
            return strlen($w) > 2; // Skip short words like "of", "in", "to"
        });

        // Take first 2-4 words
        $keywordWords = array_slice($words, 0, min(4, count($words)));

        return implode(' ', $keywordWords);
    }

    /**
     * Generate excerpt from content
     * @param string $content Article content
     * @return string Excerpt
     */
    private function generateExcerpt($content) {
        // Remove any markdown code blocks remnants
        $content = preg_replace('/```[a-z]*\s*/i', '', $content);

        // Strip HTML tags
        $text = strip_tags($content);

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Get first 160 characters (good for meta description length)
        if (strlen($text) > 160) {
            // Find last complete sentence within 160 chars
            $excerpt = substr($text, 0, 160);
            $lastPeriod = strrpos($excerpt, '.');
            $lastQuestion = strrpos($excerpt, '?');
            $lastExclaim = strrpos($excerpt, '!');

            $lastSentence = max($lastPeriod, $lastQuestion, $lastExclaim);

            if ($lastSentence !== false && $lastSentence > 100) {
                return substr($text, 0, $lastSentence + 1);
            }

            return substr($text, 0, 157) . '...';
        }

        return $text;
    }

    /**
     * Generate slug from title
     * @param string $title Title
     * @return string Slug
     */
    private function generateSlug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;

        while ($this->db->exists('posts', 'slug = ?', [$slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate or fetch featured image
     * @param string $topic Topic
     * @return string Image URL
     */
    private function generateFeaturedImage($topic) {
        try {
            // Use ImageGenerator for automatic image generation
            require_once __DIR__ . '/ImageGenerator.php';

            $imageGen = new ImageGenerator($this->campaign->image_provider ?? 'auto');
            $result = $imageGen->generateThumbnail($topic, [
                'size' => '1200x630',
                'quality' => 'standard'
            ]);

            return $result['image_url'] ?? 'https://via.placeholder.com/1200x630?text=' . urlencode($topic);

        } catch (Exception $e) {
            // Fallback to placeholder if image generation fails
            error_log("Image generation failed: " . $e->getMessage());
            return 'https://via.placeholder.com/1200x630?text=' . urlencode($topic);
        }
    }
}
