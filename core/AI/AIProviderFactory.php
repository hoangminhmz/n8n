<?php
/**
 * AI Provider Factory
 * Creates AI provider instances based on provider name
 */

class AIProviderFactory {
    /**
     * Create an AI provider instance
     *
     * @param string $provider Provider name (gemini, openai, claude)
     * @param string|null $model Optional model name
     * @return AIProvider
     * @throws Exception
     */
    public static function create($provider, $model = null) {
        $provider = strtolower(trim($provider));

        switch ($provider) {
            case 'openai':
                require_once __DIR__ . '/OpenAIProvider.php';
                $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
                return new OpenAIProvider($apiKey, $model);

            case 'claude':
                require_once __DIR__ . '/ClaudeProvider.php';
                $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';
                return new ClaudeProvider($apiKey, $model);

            case 'gemini':
            default:
                require_once __DIR__ . '/GeminiProvider.php';
                $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
                $defaultModel = $model ?? 'gemini-2.5-flash';
                return new GeminiProvider($apiKey, $defaultModel);
        }
    }

    /**
     * Get list of available providers
     *
     * @return array
     */
    public static function getAvailableProviders() {
        return [
            'gemini' => [
                'name' => 'Google Gemini',
                'models' => [
                    'gemini-2.5-flash' => 'Gemini 2.5 Flash (Recommended)',
                    'gemini-2.5-pro' => 'Gemini 2.5 Pro',
                    'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite',
                ],
                'enabled' => defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)
            ],
            'openai' => [
                'name' => 'OpenAI',
                'models' => [
                    'gpt-4' => 'GPT-4',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                ],
                'enabled' => defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)
            ],
            'claude' => [
                'name' => 'Anthropic Claude',
                'models' => [
                    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                    'claude-3-opus-20240229' => 'Claude 3 Opus',
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku'
                ],
                'enabled' => defined('CLAUDE_API_KEY') && !empty(CLAUDE_API_KEY)
            ]
        ];
    }
}
