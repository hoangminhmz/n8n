<?php
/**
 * LightBlog CMS - Google Gemini Provider
 * Integration with Google AI Studio (Gemini API)
 */

require_once __DIR__ . '/AIProvider.php';

class GeminiProvider extends AIProvider {
    private $endpoint = 'https://generativelanguage.googleapis.com/v1/models/';

    /**
     * Constructor
     * @param string $api_key Google AI Studio API key
     * @param string $model Model to use (default: gemini-2.5-flash)
     */
    public function __construct($api_key, $model = 'gemini-2.5-flash') {
        parent::__construct($api_key, $model);

        // Map common model names to correct Google API names
        // Based on: https://ai.google.dev/gemini-api/docs/models/gemini
        $modelMap = [
            // Gemini 2.5 (Latest - December 2024)
            'gemini-2.5-flash' => 'gemini-2.5-flash',
            'gemini-2.5-pro' => 'gemini-2.5-pro',
            'gemini-2.5-flash-lite' => 'gemini-2.5-flash-lite',

            // Gemini 2.0 (Experimental)
            'gemini-2.0-flash-exp' => 'gemini-2.0-flash-exp',

            // Gemini 1.5 (Stable, Legacy)
            'gemini-1.5-flash' => 'gemini-1.5-flash',
            'gemini-1.5-flash-8b' => 'gemini-1.5-flash-8b',
            'gemini-1.5-pro' => 'gemini-1.5-pro',

            // Convenience aliases
            'gemini-pro' => 'gemini-2.5-pro', // Latest Pro
            'gemini-flash' => 'gemini-2.5-flash', // Latest Flash
            'gemini-lite' => 'gemini-2.5-flash-lite', // Lightest/Fastest
        ];

        // Use mapped model name if exists
        if (isset($modelMap[$model])) {
            $this->model = $modelMap[$model];
        }
    }

    /**
     * Generate content using Google Gemini
     * @param string $prompt User prompt
     * @param array $options Options (system_prompt, temperature, max_tokens, campaign_id)
     * @return array
     */
    public function generate($prompt, $options = []) {
        if (!$this->validateApiKey()) {
            throw new Exception('Google AI Studio API key not configured');
        }

        // Combine system prompt with user prompt for Gemini
        $systemPrompt = $options['system_prompt'] ?? 'You are a professional content writer who creates engaging, SEO-optimized blog posts.';
        $fullPrompt = $systemPrompt . "\n\n" . $prompt;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $fullPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 2000,
                'topP' => 0.8,
                'topK' => 40
            ]
        ];

        try {
            // Build endpoint URL with API key
            $url = $this->endpoint . $this->model . ':generateContent?key=' . $this->api_key;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('HTTP request failed: ' . $error);
            }

            $result = json_decode($response, true);

            if ($httpCode !== 200) {
                $errorMsg = $result['error']['message'] ?? $response;
                throw new Exception('Gemini API Error: ' . $errorMsg);
            }

            if (isset($result['error'])) {
                throw new Exception('Gemini Error: ' . $result['error']['message']);
            }

            // Extract content from Gemini response
            $content = '';
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $result['candidates'][0]['content']['parts'][0]['text'];
            }

            // Estimate tokens (Gemini uses different token counting, approximate for now)
            $tokens = isset($result['usageMetadata']['totalTokenCount'])
                ? $result['usageMetadata']['totalTokenCount']
                : (int)(strlen($content) / 4); // Rough estimate: 1 token ≈ 4 characters

            $cost = $this->estimateCost($tokens);

            // Log usage
            $this->logUsage($tokens, $cost, $options['campaign_id'] ?? null);

            return [
                'content' => $content,
                'tokens' => $tokens,
                'cost' => $cost
            ];

        } catch (Exception $e) {
            throw new Exception('Gemini generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Estimate cost based on model and tokens
     * @param int $tokens
     * @return float
     */
    public function estimateCost($tokens) {
        // Gemini pricing as of 2025
        $costs = [
            'gemini-2.5-flash' => 0.00015,       // $0.15 per 1M tokens (newest, fastest)
            'gemini-2.0-flash-exp' => 0.00010,   // Experimental, may be free/cheaper
            'gemini-1.5-pro-latest' => 0.00125,  // $1.25 per 1M tokens
            'gemini-flash-latest' => 0.00025,    // $0.25 per 1M tokens
            'gemini-pro' => 0.0005               // Legacy model
        ];

        $rate = $costs[$this->model] ?? 0.0002;
        return ($tokens / 1000) * $rate;
    }

    /**
     * Get provider name
     * @return string
     */
    public function getName() {
        return 'gemini';
    }

    /**
     * Get available models
     * @return array
     */
    public function getAvailableModels() {
        return [
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Newest, fastest, recommended)',
            'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash Experimental',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Most capable, multimodal)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast and efficient)',
            'gemini-pro' => 'Gemini Pro (Legacy)'
        ];
    }

    /**
     * Generate image - Gemini doesn't have simple image generation yet
     * Falls back to PHP GD-based generation
     * @param string $prompt Text description of the image
     * @param array $options Options (size, quality, style, campaign_id)
     * @return array
     */
    public function generateImage($prompt, $options = []) {
        // Note: Google Imagen 3 requires Vertex AI setup which is complex
        // For now, we throw exception and let ImageGenerator fallback to PHP GD
        // This keeps the API simple while maintaining quality

        throw new Exception('Gemini provider does not support direct image generation. Please configure OpenAI API key to use AI-generated thumbnails with DALL-E 3.');
    }
}
