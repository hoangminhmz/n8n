<?php
/**
 * LightBlog CMS - AI Prompt Manager
 * Manages customizable AI prompts for content generation
 */

class PromptManager {
    private $db;
    private $cache = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get prompt by template key with variable replacement
     * @param string $templateKey Template key (e.g., 'post_title', 'campaign_topics')
     * @param array $variables Associative array of variables to replace
     * @return string Final prompt with variables replaced
     */
    public function getPrompt($templateKey, $variables = []) {
        // Check cache first
        if (isset($this->cache[$templateKey])) {
            $template = $this->cache[$templateKey];
        } else {
            // Fetch from database
            try {
                $template = $this->db->queryOne(
                    "SELECT * FROM prompt_templates WHERE template_key = ?",
                    [$templateKey]
                );
            } catch (Exception $e) {
                // Database error - use fallback
                $template = null;
            }

            if (!$template) {
                // Use fallback default prompts if template not found in database
                $template = $this->getFallbackTemplate($templateKey);

                if (!$template) {
                    throw new Exception("Prompt template not found: {$templateKey}");
                }
            }

            // Cache it
            $this->cache[$templateKey] = $template;
        }

        // Use custom prompt if active, otherwise use default
        $prompt = ($template->is_active && !empty($template->custom_prompt))
            ? $template->custom_prompt
            : $template->default_prompt;

        // Replace variables
        $finalPrompt = $this->replaceVariables($prompt, $variables);

        return $finalPrompt;
    }

    /**
     * Get fallback template if database is not available
     * @param string $templateKey Template key
     * @return object|null Template object or null
     */
    private function getFallbackTemplate($templateKey) {
        $fallbacks = [
            'campaign_topics' => [
                'template_key' => 'campaign_topics',
                'template_name' => 'Campaign Topic Generation',
                'category' => 'campaign',
                'default_prompt' => 'Generate {{count}} unique, engaging blog topic ideas for the {{niche}} niche.

Seed keywords: {{seed_keywords}}
Target audience: {{target_audience}}

AVOID these existing topics (be creative and different):
{{existing_topics}}

Requirements:
- Each topic should be specific and actionable
- Include search-friendly keywords naturally
- Mix formats: how-to, listicles, guides, comparisons
- Consider current trends in {{year}}
- Topics should rank well in Google

Output as JSON array:
["Topic 1", "Topic 2", ...]',
                'custom_prompt' => null,
                'is_active' => 0
            ],
            'post_title' => [
                'template_key' => 'post_title',
                'template_name' => 'Post Title Generation',
                'category' => 'post',
                'default_prompt' => 'Create an engaging, SEO-optimized title for a blog post about: {{topic}}

Primary keyword: {{primary_keyword}}
Niche: {{niche}}
Tone: {{tone}}
Year: {{year}}

Requirements:
- Include the primary keyword naturally
- Keep it under 60 characters for SEO
- Make it compelling and click-worthy
- Match the {{tone}} tone

Output just the title, nothing else.',
                'custom_prompt' => null,
                'is_active' => 0
            ]
        ];

        if (isset($fallbacks[$templateKey])) {
            return (object) $fallbacks[$templateKey];
        }

        return null;
    }

    /**
     * Replace variables in prompt template
     * @param string $prompt Prompt template with {{variable}} placeholders
     * @param array $variables Associative array of variables
     * @return string Prompt with variables replaced
     */
    private function replaceVariables($prompt, $variables) {
        foreach ($variables as $key => $value) {
            // Handle array values (convert to string)
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // Handle object values (convert to JSON)
            if (is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            // Replace {{key}} with value
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        // Clean up any remaining unreplaced variables (optional safety)
        // $prompt = preg_replace('/\{\{[^}]+\}\}/', '', $prompt);

        return $prompt;
    }

    /**
     * Get all templates for a category
     * @param string $category Category (post, campaign, image)
     * @return array Array of template objects
     */
    public function getTemplatesByCategory($category) {
        return $this->db->query(
            "SELECT * FROM prompt_templates WHERE category = ? ORDER BY template_name",
            [$category]
        );
    }

    /**
     * Get all templates
     * @return array All templates
     */
    public function getAllTemplates() {
        return $this->db->query(
            "SELECT * FROM prompt_templates ORDER BY category, template_name"
        );
    }

    /**
     * Update template (custom prompt and active status)
     * @param string $templateKey Template key
     * @param string $customPrompt Custom prompt text
     * @param bool $isActive Whether to use custom prompt
     * @param int $userId User ID making the change
     * @return bool Success
     */
    public function updateTemplate($templateKey, $customPrompt, $isActive, $userId = null) {
        $result = $this->db->update('prompt_templates',
            [
                'custom_prompt' => $customPrompt,
                'is_active' => $isActive ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId
            ],
            'template_key = :key',
            ['key' => $templateKey]
        );

        // Clear cache
        unset($this->cache[$templateKey]);

        return $result;
    }

    /**
     * Reset template to default
     * @param string $templateKey Template key
     * @return bool Success
     */
    public function resetToDefault($templateKey) {
        $result = $this->db->update('prompt_templates',
            [
                'custom_prompt' => null,
                'is_active' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'template_key = :key',
            ['key' => $templateKey]
        );

        // Clear cache
        unset($this->cache[$templateKey]);

        return $result;
    }

    /**
     * Get available variables for a template
     * @param string $templateKey Template key
     * @return array Array of variable names and descriptions
     */
    public function getVariables($templateKey) {
        $template = $this->db->queryOne(
            "SELECT variables FROM prompt_templates WHERE template_key = ?",
            [$templateKey]
        );

        if (!$template || empty($template->variables)) {
            return [];
        }

        return json_decode($template->variables, true) ?? [];
    }

    /**
     * Validate prompt (check for required variables)
     * @param string $templateKey Template key
     * @param string $prompt Prompt to validate
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validatePrompt($templateKey, $prompt) {
        $errors = [];
        $warnings = [];

        // Check prompt length
        if (strlen($prompt) > 4000) {
            $errors[] = 'Prompt is too long (max 4000 characters)';
        }

        if (strlen($prompt) < 10) {
            $errors[] = 'Prompt is too short (min 10 characters)';
        }

        // Get expected variables
        $variables = $this->getVariables($templateKey);

        // Check for critical variables based on template type
        $criticalVars = $this->getCriticalVariables($templateKey);

        foreach ($criticalVars as $varName) {
            if (strpos($prompt, '{{' . $varName . '}}') === false) {
                $warnings[] = "Missing recommended variable: {{" . $varName . "}}";
            }
        }

        // Check for invalid variable syntax
        if (preg_match_all('/\{\{([^}]+)\}\}/', $prompt, $matches)) {
            foreach ($matches[1] as $varName) {
                if (!in_array('{{' . $varName . '}}', $variables)) {
                    $warnings[] = "Unknown variable: {{" . $varName . "}}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get critical variables for a template type
     * @param string $templateKey Template key
     * @return array Array of critical variable names
     */
    private function getCriticalVariables($templateKey) {
        $criticalMap = [
            'post_title' => ['topic', 'primary_keyword'],
            'post_outline' => ['topic', 'primary_keywords'],
            'post_content' => ['outline', 'tone'],
            'post_meta_description' => ['title', 'focus_keyword'],
            'campaign_topics' => ['niche', 'seed_keywords', 'count'],
            'image_generation' => ['topic'],
            'image_search' => ['topic']
        ];

        return $criticalMap[$templateKey] ?? ['topic'];
    }

    /**
     * Test a prompt with sample data
     * @param string $prompt Prompt to test
     * @param array $sampleVariables Sample variable values
     * @return string Prompt with sample variables replaced
     */
    public function testPrompt($prompt, $sampleVariables) {
        return $this->replaceVariables($prompt, $sampleVariables);
    }

    /**
     * Export all custom prompts as JSON
     * @return string JSON string of all custom prompts
     */
    public function exportCustomPrompts() {
        $templates = $this->db->query(
            "SELECT template_key, template_name, custom_prompt, is_active
             FROM prompt_templates
             WHERE custom_prompt IS NOT NULL"
        );

        $export = [];
        foreach ($templates as $template) {
            $export[$template->template_key] = [
                'name' => $template->template_name,
                'prompt' => $template->custom_prompt,
                'is_active' => (bool)$template->is_active
            ];
        }

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import custom prompts from JSON
     * @param string $json JSON string of prompts
     * @param int $userId User ID making the import
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function importCustomPrompts($json, $userId = null) {
        $success = 0;
        $failed = 0;
        $errors = [];

        $prompts = json_decode($json, true);

        if (!$prompts) {
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => ['Invalid JSON format']
            ];
        }

        foreach ($prompts as $key => $data) {
            try {
                // Check if template exists
                $exists = $this->db->exists('prompt_templates', 'template_key = ?', [$key]);

                if (!$exists) {
                    $errors[] = "Template '{$key}' does not exist";
                    $failed++;
                    continue;
                }

                // Update the template
                $this->updateTemplate(
                    $key,
                    $data['prompt'],
                    $data['is_active'] ?? true,
                    $userId
                );

                $success++;

            } catch (Exception $e) {
                $errors[] = "Failed to import '{$key}': " . $e->getMessage();
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Get prompt usage statistics
     * @return array Statistics about prompt usage
     */
    public function getStatistics() {
        $stats = $this->db->queryOne("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as custom_active,
                SUM(CASE WHEN custom_prompt IS NOT NULL THEN 1 ELSE 0 END) as has_custom
            FROM prompt_templates
        ");

        return [
            'total_templates' => (int)$stats->total,
            'custom_prompts_created' => (int)$stats->has_custom,
            'custom_prompts_active' => (int)$stats->custom_active,
            'using_defaults' => (int)$stats->total - (int)$stats->custom_active
        ];
    }
}
