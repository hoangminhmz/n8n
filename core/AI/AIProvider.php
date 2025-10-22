<?php
/**
 * LightBlog CMS - AI Provider Interface
 * Abstract class for AI content generation providers
 */

abstract class AIProvider {
    protected $api_key;
    protected $model;
    protected $db;

    /**
     * Constructor
     * @param string $api_key API key for the provider
     * @param string $model Model to use
     */
    public function __construct($api_key, $model = null) {
        $this->api_key = $api_key;
        $this->model = $model;
        $this->db = Database::getInstance();
    }

    /**
     * Generate content
     * @param string $prompt The prompt to send to the AI
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array ['content' => string, 'tokens' => int, 'cost' => float]
     */
    abstract public function generate($prompt, $options = []);

    /**
     * Estimate cost based on token usage
     * @param int $tokens Number of tokens
     * @return float Cost in USD
     */
    abstract public function estimateCost($tokens);

    /**
     * Get provider name
     * @return string
     */
    abstract public function getName();

    /**
     * Get available models
     * @return array
     */
    abstract public function getAvailableModels();

    /**
     * Log AI usage
     * @param int $tokens Tokens used
     * @param float $cost Cost
     * @param int|null $campaign_id Campaign ID
     */
    protected function logUsage($tokens, $cost, $campaign_id = null) {
        try {
            $this->db->insert('ai_usage', [
                'provider' => $this->getName(),
                'model' => $this->model,
                'tokens_used' => $tokens,
                'cost' => $cost,
                'campaign_id' => $campaign_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Failed to log AI usage: ' . $e->getMessage());
        }
    }

    /**
     * Make HTTP request
     * @param string $url URL to request
     * @param array $data Data to send
     * @param array $headers HTTP headers
     * @return array Response
     */
    protected function makeRequest($url, $data, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('HTTP request failed: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('HTTP request failed with code: ' . $httpCode . ' Response: ' . $response);
        }

        return json_decode($response, true);
    }

    /**
     * Validate API key
     * @return bool
     */
    public function validateApiKey() {
        return !empty($this->api_key);
    }
}
