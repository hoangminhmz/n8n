<?php
/**
 * LightBlog CMS - Anthropic Claude Provider
 * Integration with Anthropic Claude API
 */

require_once __DIR__ . '/AIProvider.php';

class ClaudeProvider extends AIProvider {
    private $endpoint = 'https://api.anthropic.com/v1/messages';
    private $version = '2023-06-01';

    /**
     * Constructor
     * @param string $api_key Anthropic API key
     * @param string $model Model to use (default: claude-3-sonnet-20240229)
     */
    public function __construct($api_key, $model = 'claude-3-sonnet-20240229') {
        parent::__construct($api_key, $model);
    }

    /**
     * Generate content using Claude
     * @param string $prompt User prompt
     * @param array $options Options (system_prompt, temperature, max_tokens, campaign_id)
     * @return array
     */
    public function generate($prompt, $options = []) {
        if (!$this->validateApiKey()) {
            throw new Exception('Claude API key not configured');
        }

        $data = [
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        // Add system prompt if provided
        if (isset($options['system_prompt'])) {
            $data['system'] = $options['system_prompt'];
        }

        // Add temperature if provided
        if (isset($options['temperature'])) {
            $data['temperature'] = $options['temperature'];
        }

        try {
            $headers = [
                'Content-Type: application/json',
                'x-api-key: ' . $this->api_key,
                'anthropic-version: ' . $this->version
            ];

            $result = $this->makeRequest($this->endpoint, $data, $headers);

            if (isset($result['error'])) {
                throw new Exception('Claude Error: ' . $result['error']['message']);
            }

            $content = $result['content'][0]['text'] ?? '';
            $inputTokens = $result['usage']['input_tokens'] ?? 0;
            $outputTokens = $result['usage']['output_tokens'] ?? 0;
            $tokens = $inputTokens + $outputTokens;
            $cost = $this->estimateCost($tokens, $inputTokens, $outputTokens);

            // Log usage
            $this->logUsage($tokens, $cost, $options['campaign_id'] ?? null);

            return [
                'content' => $content,
                'tokens' => $tokens,
                'cost' => $cost
            ];

        } catch (Exception $e) {
            throw new Exception('Claude generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Estimate cost based on model and tokens
     * @param int $totalTokens Total tokens
     * @param int $inputTokens Input tokens
     * @param int $outputTokens Output tokens
     * @return float
     */
    public function estimateCost($totalTokens, $inputTokens = 0, $outputTokens = 0) {
        // If individual tokens not provided, estimate 50/50 split
        if ($inputTokens === 0 && $outputTokens === 0) {
            $inputTokens = $totalTokens / 2;
            $outputTokens = $totalTokens / 2;
        }

        $costs = [
            'claude-3-opus-20240229' => ['input' => 0.015, 'output' => 0.075],
            'claude-3-sonnet-20240229' => ['input' => 0.003, 'output' => 0.015],
            'claude-3-haiku-20240307' => ['input' => 0.00025, 'output' => 0.00125]
        ];

        $rate = $costs[$this->model] ?? $costs['claude-3-sonnet-20240229'];

        $inputCost = ($inputTokens / 1000) * $rate['input'];
        $outputCost = ($outputTokens / 1000) * $rate['output'];

        return $inputCost + $outputCost;
    }

    /**
     * Get provider name
     * @return string
     */
    public function getName() {
        return 'claude';
    }

    /**
     * Get available models
     * @return array
     */
    public function getAvailableModels() {
        return [
            'claude-3-opus-20240229' => 'Claude 3 Opus (Most capable)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Balanced performance)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fast and economical)'
        ];
    }
}
