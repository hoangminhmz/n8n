<?php
/**
 * LightBlog CMS - OpenAI Provider
 * Integration with OpenAI API (GPT-4, GPT-3.5)
 */

require_once __DIR__ . '/AIProvider.php';

class OpenAIProvider extends AIProvider {
    private $endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Constructor
     * @param string $api_key OpenAI API key
     * @param string $model Model to use (default: gpt-4)
     */
    public function __construct($api_key, $model = 'gpt-4') {
        parent::__construct($api_key, $model);
    }

    /**
     * Generate content using OpenAI
     * @param string $prompt User prompt
     * @param array $options Options (system_prompt, temperature, max_tokens, campaign_id)
     * @return array
     */
    public function generate($prompt, $options = []) {
        if (!$this->validateApiKey()) {
            throw new Exception('OpenAI API key not configured');
        }

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $options['system_prompt'] ?? 'You are a professional content writer who creates engaging, SEO-optimized blog posts.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000
        ];

        try {
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ];

            $result = $this->makeRequest($this->endpoint, $data, $headers);

            if (isset($result['error'])) {
                throw new Exception('OpenAI Error: ' . $result['error']['message']);
            }

            $content = $result['choices'][0]['message']['content'] ?? '';
            $tokens = $result['usage']['total_tokens'] ?? 0;
            $cost = $this->estimateCost($tokens);

            // Log usage
            $this->logUsage($tokens, $cost, $options['campaign_id'] ?? null);

            return [
                'content' => $content,
                'tokens' => $tokens,
                'cost' => $cost
            ];

        } catch (Exception $e) {
            throw new Exception('OpenAI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Estimate cost based on model and tokens
     * @param int $tokens
     * @return float
     */
    public function estimateCost($tokens) {
        $costs = [
            'gpt-4' => 0.045,        // Average of input and output
            'gpt-4-turbo' => 0.015,
            'gpt-3.5-turbo' => 0.002
        ];

        $rate = $costs[$this->model] ?? $costs['gpt-4'];
        return ($tokens / 1000) * $rate;
    }

    /**
     * Get provider name
     * @return string
     */
    public function getName() {
        return 'openai';
    }

    /**
     * Get available models
     * @return array
     */
    public function getAvailableModels() {
        return [
            'gpt-4' => 'GPT-4 (Most capable, higher cost)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Fast and capable)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast and economical)'
        ];
    }

    /**
     * Generate image using DALL-E 3
     * @param string $prompt Text description of the image
     * @param array $options Options (size, quality, style, campaign_id)
     * @return array
     */
    public function generateImage($prompt, $options = []) {
        if (!$this->validateApiKey()) {
            throw new Exception('OpenAI API key not configured');
        }

        $data = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $options['size'] ?? '1024x1024', // 1024x1024, 1024x1792, 1792x1024
            'quality' => $options['quality'] ?? 'standard', // standard or hd
            'style' => $options['style'] ?? 'vivid' // vivid or natural
        ];

        try {
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ];

            $result = $this->makeRequest('https://api.openai.com/v1/images/generations', $data, $headers);

            if (isset($result['error'])) {
                throw new Exception('DALL-E Error: ' . $result['error']['message']);
            }

            $imageUrl = $result['data'][0]['url'] ?? '';

            if (empty($imageUrl)) {
                throw new Exception('No image URL returned from DALL-E');
            }

            // DALL-E 3 pricing: $0.040 per image for standard 1024x1024, $0.080 for HD
            $cost = ($options['quality'] === 'hd') ? 0.080 : 0.040;

            // For larger sizes
            if (in_array($data['size'], ['1024x1792', '1792x1024'])) {
                $cost = ($options['quality'] === 'hd') ? 0.120 : 0.080;
            }

            // Log usage (use 0 tokens for image generation, cost is per image)
            $this->logUsage(0, $cost, $options['campaign_id'] ?? null);

            return [
                'image_url' => $imageUrl,
                'cost' => $cost,
                'provider' => 'openai',
                'model' => 'dall-e-3'
            ];

        } catch (Exception $e) {
            throw new Exception('DALL-E image generation failed: ' . $e->getMessage());
        }
    }
}
