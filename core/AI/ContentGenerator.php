<?php
/**
 * LightBlog CMS - Content Generator
 * Main content generation engine using AI providers
 */

class ContentGenerator {
    private $aiProvider;
    private $campaign;
    private $db;

    /**
     * Constructor
     * @param object $campaign Campaign object
     */
    public function __construct($campaign) {
        $this->campaign = $campaign;
        $this->db = Database::getInstance();
        $this->initAIProvider();
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

        // Step 6: Generate SEO fields
        $focusKeyword = $keywords['primary'][0] ?? $topic;
        $canonicalUrl = SITE_URL . BASE_PATH . 'post/' . $this->generateSlug($metadata['title']);

        // Step 7: Extract FAQ data
        require_once __DIR__ . '/../SEO/FAQExtractor.php';
        $faqExtractor = new FAQExtractor();
        $faqs = $faqExtractor->extract($content);
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

        $prompt = "Create a detailed SEO-optimized article outline for: \"{$topic}\"

Primary keywords: {$primaryKeywords}
LSI keywords: {$lsiKeywords}

Requirements:
- Include H1, H2, and H3 headings
- Add an FAQ section with 5 questions
- Target word count: {$this->campaign->word_count_min}-{$this->campaign->word_count_max} words
- Optimize for featured snippets
- Include introduction and conclusion

Format as JSON:
{
  \"h1\": \"Main title\",
  \"sections\": [
    {\"h2\": \"Section title\", \"h3\": [\"Subsection 1\", \"Subsection 2\"]},
    ...
  ],
  \"faqs\": [
    {\"question\": \"Q1\", \"answer_hint\": \"brief hint\"},
    ...
  ]
}";

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

        $prompt = "Write a comprehensive, engaging blog article based on this outline:

" . json_encode($outline, JSON_PRETTY_PRINT) . "

Requirements:
- Tone: {$tone}
- Naturally include these keywords: {$primaryKeywords}
- Write in clear, engaging paragraphs
- Add relevant examples and statistics
- Include [PRODUCT_LINK] markers where affiliate products should be mentioned
- Format as HTML with proper heading tags (h1, h2, h3)
- Add bullet points and numbered lists where appropriate
- Make it SEO-optimized and reader-friendly

Write the complete article content now:";

        $result = $this->aiProvider->generate($prompt, [
            'temperature' => $this->campaign->ai_temperature ?? 0.7,
            'max_tokens' => 4000,
            'campaign_id' => $this->campaign->id,
            'system_prompt' => "You are an expert content writer specializing in {$this->campaign->niche}. Write engaging, informative content that ranks well in search engines."
        ]);

        return $result['content'];
    }

    /**
     * Generate metadata (title, description, etc.)
     * @param string $topic Topic
     * @param array $keywords Keywords
     * @return array Metadata
     */
    private function generateMetadata($topic, $keywords) {
        $primaryKeyword = $keywords['primary'][0] ?? $topic;

        $prompt = "Generate SEO metadata for an article about: \"{$topic}\"

Primary keyword: {$primaryKeyword}

Create:
1. An SEO-optimized title (55-60 characters, include keyword)
2. A meta description (150-160 characters, compelling, include keyword)
3. An alternative SEO title for rich snippets

Format as JSON:
{
  \"title\": \"Catchy title here\",
  \"seo_title\": \"SEO optimized title\",
  \"meta_description\": \"Compelling description\"
}";

        $result = $this->aiProvider->generate($prompt, [
            'temperature' => 0.9,
            'max_tokens' => 500,
            'campaign_id' => $this->campaign->id
        ]);

        $metadata = json_decode($result['content'], true);

        if (!$metadata) {
            // Fallback
            $metadata = [
                'title' => $topic,
                'seo_title' => $topic,
                'meta_description' => substr($topic, 0, 155)
            ];
        }

        return $metadata;
    }

    /**
     * Generate excerpt from content
     * @param string $content Article content
     * @return string Excerpt
     */
    private function generateExcerpt($content) {
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > 200) {
            return substr($text, 0, 197) . '...';
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
        // For now, return a placeholder
        // In production, integrate with image APIs like Unsplash, Pexels, or AI image generation
        return 'https://via.placeholder.com/1200x630?text=' . urlencode($topic);
    }
}
