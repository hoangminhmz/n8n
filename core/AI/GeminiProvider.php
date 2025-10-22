<?php
/**
 * LightBlog CMS - Google Gemini Provider
 * Integration with Google AI Studio (Gemini API)
 */

require_once __DIR__ . '/AIProvider.php';

class GeminiProvider extends AIProvider {
    private $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Constructor
     * @param string $api_key Google AI Studio API key
     * @param string $model Model to use (default: gemini-pro)
     */
    public function __construct($api_key, $model = 'gemini-pro') {
        parent::__construct($api_key, $model);
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
        // Gemini Pro pricing as of 2024
        $costs = [
            'gemini-pro' => 0.0005,        // $0.50 per 1M characters (approx $0.0005 per 1K tokens)
            'gemini-1.5-pro' => 0.00125,   // $1.25 per 1M tokens input
            'gemini-1.5-flash' => 0.00025  // $0.25 per 1M tokens
        ];

        $rate = $costs[$this->model] ?? $costs['gemini-pro'];
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
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Most capable, multimodal)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast and efficient)',
            'gemini-pro' => 'Gemini Pro (Balanced performance)'
        ];
    }
}
