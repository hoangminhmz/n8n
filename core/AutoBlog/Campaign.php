<?php
/**
 * LightBlog CMS - Campaign Manager
 * Manages AI auto-blogging campaigns
 */

class Campaign {
    private $db;
    private $promptManager;

    public function __construct() {
        $this->db = Database::getInstance();

        // Initialize PromptManager for customizable prompts
        require_once __DIR__ . '/../AI/PromptManager.php';
        $this->promptManager = new PromptManager();
    }

    /**
     * Create a new campaign
     */
    public function create($data) {
        return $this->db->insert('campaigns', [
            'name' => $data['name'],
            'niche' => $data['niche'] ?? '',
            'status' => 'active',
            'goal' => $data['goal'] ?? 'traffic',
            'seed_keywords' => json_encode($data['seed_keywords'] ?? []),
            'target_count' => $data['target_count'] ?? 100,
            'posts_per_day' => $data['posts_per_day'] ?? 3,
            'content_types' => json_encode($data['content_types'] ?? ['article']),
            'word_count_min' => $data['word_count_min'] ?? 1500,
            'word_count_max' => $data['word_count_max'] ?? 2500,
            'ai_provider' => $data['ai_provider'] ?? 'openai',
            'ai_model' => $data['ai_model'] ?? 'gpt-4',
            'ai_temperature' => $data['ai_temperature'] ?? 0.7,
            'tone' => $data['tone'] ?? 'professional',
            'language' => $data['language'] ?? 'en',
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'publish_times' => json_encode($data['publish_times'] ?? ['09:00', '14:00', '20:00']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get campaign by ID
     */
    public function get($id) {
        return $this->db->queryOne("SELECT * FROM campaigns WHERE id = ?", [$id]);
    }

    /**
     * Get all campaigns
     */
    public function getAll($status = null) {
        if ($status) {
            return $this->db->query("SELECT * FROM campaigns WHERE status = ? ORDER BY created_at DESC", [$status]);
        }
        return $this->db->query("SELECT * FROM campaigns ORDER BY created_at DESC");
    }

    /**
     * Update campaign
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('campaigns', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete campaign
     */
    public function delete($id) {
        return $this->db->delete('campaigns', 'id = ?', [$id]);
    }

    /**
     * Get campaign progress
     */
    public function getProgress($campaign_id) {
        $campaign = $this->get($campaign_id);
        if (!$campaign) {
            return null;
        }

        $published = $this->db->count('posts', 'campaign_id = ? AND status = ?', [$campaign_id, 'published']);
        $draft = $this->db->count('posts', 'campaign_id = ? AND status = ?', [$campaign_id, 'draft']);
        $total = $published + $draft;

        return [
            'published' => $published,
            'draft' => $draft,
            'total' => $total,
            'target' => $campaign->target_count,
            'percentage' => $campaign->target_count > 0 ? round(($published / $campaign->target_count) * 100) : 0
        ];
    }

    /**
     * Generate topics for campaign using AI with duplicate detection
     */
    public function generateTopics($campaign_id, $count = 10) {
        $campaign = $this->get($campaign_id);
        if (!$campaign) {
            return [];
        }

        $seedKeywords = json_decode($campaign->seed_keywords, true) ?? [];

        // Get existing topics to avoid duplicates
        $existingTopics = $this->getExistingTopics($campaign_id);

        // Try AI-powered topic generation first
        try {
            $aiTopics = $this->generateTopicsWithAI($campaign, $seedKeywords, $count, $existingTopics);
            if (!empty($aiTopics)) {
                // Filter out duplicates
                $uniqueTopics = $this->filterDuplicates($aiTopics, $existingTopics);

                // If we got enough unique topics, return them
                if (count($uniqueTopics) >= min($count, count($aiTopics) * 0.7)) {
                    return array_slice($uniqueTopics, 0, $count);
                }

                // If not enough unique topics, generate more
                if (count($uniqueTopics) < $count) {
                    $additionalCount = $count - count($uniqueTopics);
                    $moreTopics = $this->generateTopicsWithAI($campaign, $seedKeywords, $additionalCount * 2, array_merge($existingTopics, $uniqueTopics));
                    $uniqueTopics = array_merge($uniqueTopics, $this->filterDuplicates($moreTopics, array_merge($existingTopics, $uniqueTopics)));
                }

                return array_slice($uniqueTopics, 0, $count);
            }
        } catch (Exception $e) {
            // Log detailed error
            error_log("AI topic generation failed for campaign {$campaign_id}: " . $e->getMessage());

            // Throw error so user knows AI failed (don't silently fall back to templates)
            throw new Exception("AI topic generation failed: " . $e->getMessage() . ". Please check your AI provider settings and API keys in Admin → Settings.");
        }

        return [];
    }

    /**
     * Get existing topics from posts and queue to avoid duplicates
     */
    private function getExistingTopics($campaign_id) {
        $topics = [];

        // Get topics from published and draft posts
        $posts = $this->db->query("
            SELECT DISTINCT title
            FROM posts
            WHERE campaign_id = ?
        ", [$campaign_id]);

        foreach ($posts as $post) {
            $topics[] = strtolower(trim($post->title));
        }

        // Get topics from queue
        $queueItems = $this->db->query("
            SELECT DISTINCT topic
            FROM ai_queue
            WHERE campaign_id = ?
        ", [$campaign_id]);

        foreach ($queueItems as $item) {
            $topics[] = strtolower(trim($item->topic));
        }

        return $topics;
    }

    /**
     * Filter out duplicate topics using similarity matching
     */
    private function filterDuplicates($newTopics, $existingTopics) {
        $unique = [];

        foreach ($newTopics as $topic) {
            $isDuplicate = false;
            $topicLower = strtolower(trim($topic));

            // Check exact match
            if (in_array($topicLower, $existingTopics)) {
                continue;
            }

            // Check similarity with existing topics
            foreach ($existingTopics as $existing) {
                $similarity = 0;
                similar_text($topicLower, $existing, $similarity);

                // If topics are more than 80% similar, consider them duplicates
                if ($similarity > 80) {
                    $isDuplicate = true;
                    break;
                }
            }

            // Check similarity with already selected unique topics
            foreach ($unique as $selectedTopic) {
                $similarity = 0;
                similar_text($topicLower, strtolower($selectedTopic), $similarity);

                if ($similarity > 80) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $unique[] = $topic;
            }
        }

        return $unique;
    }

    /**
     * Generate topics using AI based on campaign settings
     */
    private function generateTopicsWithAI($campaign, $seedKeywords, $count, $existingTopics = []) {
        // Initialize AI provider based on campaign settings
        $provider = $campaign->ai_provider ?? 'gemini';
        $model = $campaign->ai_model ?? 'gemini-2.5-flash';

        // Get API key
        $apiKey = '';
        switch ($provider) {
            case 'openai':
                require_once __DIR__ . '/../AI/OpenAIProvider.php';
                $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
                $aiProvider = new OpenAIProvider($apiKey, $model);
                break;

            case 'claude':
                require_once __DIR__ . '/../AI/ClaudeProvider.php';
                $apiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';
                $aiProvider = new ClaudeProvider($apiKey, $model);
                break;

            case 'gemini':
                require_once __DIR__ . '/../AI/GeminiProvider.php';
                $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
                $aiProvider = new GeminiProvider($apiKey, $model);
                break;

            default:
                throw new Exception('Unsupported AI provider: ' . $provider);
        }

        if (empty($apiKey)) {
            throw new Exception('API key not configured for ' . $provider);
        }

        // Build intelligent prompt
        $keywordsStr = implode(', ', $seedKeywords);
        $currentYear = date('Y');
        $tone = $campaign->tone ?? 'professional';
        $niche = $campaign->niche ?? 'general';
        $goal = $campaign->goal ?? 'traffic';

        $goalContext = [
            'traffic' => 'Focus on trending, viral, and highly searchable topics that attract maximum organic traffic.',
            'affiliate' => 'Focus on product reviews, comparisons, buying guides, and topics with high commercial intent.',
            'authority' => 'Focus on comprehensive, in-depth topics that establish expertise and thought leadership.'
        ];

        // Build list of existing topics to avoid
        $existingTopicsStr = '';
        if (!empty($existingTopics)) {
            $sampleExisting = array_slice($existingTopics, 0, 20); // Show up to 20 examples
            $existingTopicsStr = "These topics already exist. Generate COMPLETELY DIFFERENT topics:\n";
            foreach ($sampleExisting as $existing) {
                $existingTopicsStr .= "- " . $existing . "\n";
            }
            if (count($existingTopics) > 20) {
                $existingTopicsStr .= "... and " . (count($existingTopics) - 20) . " more.\n";
            }
            $existingTopicsStr .= "\nDo NOT create topics similar to these. Be creative and explore NEW angles!";
        }

        // Use customizable prompt from PromptManager
        $prompt = $this->promptManager->getPrompt('campaign_topics', [
            'count' => $count,
            'niche' => $niche,
            'seed_keywords' => $keywordsStr,
            'target_audience' => $campaign->target_audience ?? 'general audience',
            'existing_topics' => $existingTopicsStr,
            'year' => $currentYear
        ]);

        // Call AI to generate topics
        $result = $aiProvider->generate($prompt, [
            'temperature' => 0.9, // Higher creativity for diverse topics
            'max_tokens' => 2000,
            'campaign_id' => $campaign->id
        ]);

        // Parse JSON response
        $content = trim($result['content']);

        // Try to extract JSON array from response (in case AI adds extra text)
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $content = $matches[0];
        }

        $topics = json_decode($content, true);

        if (!is_array($topics) || empty($topics)) {
            throw new Exception('Invalid AI response format');
        }

        // Return requested number of topics
        return array_slice($topics, 0, $count);
    }

    /**
     * Generate topics using simple templates (fallback method)
     */
    private function generateTopicsWithTemplates($seedKeywords, $count) {
        $topics = [];
        $currentYear = date('Y');

        $templates = [
            'How to {keyword}',
            'Best {keyword} in ' . $currentYear,
            '{keyword}: Complete Guide',
            '{keyword} Tips and Tricks',
            'Top 10 {keyword} for ' . $currentYear,
            '{keyword} for Beginners',
            'Advanced {keyword} Strategies',
            '{keyword} vs Alternatives: Which is Better?',
            'Why {keyword} Matters in ' . $currentYear,
            '{keyword}: Expert Review and Analysis',
            'The Ultimate {keyword} Checklist',
            '{keyword} Mistakes to Avoid',
            '{keyword} Case Study: Real Results',
            'Future of {keyword}: Trends and Predictions'
        ];

        foreach ($seedKeywords as $keyword) {
            foreach ($templates as $template) {
                if (count($topics) >= $count) break 2;
                $topics[] = str_replace('{keyword}', ucfirst($keyword), $template);
            }
        }

        return array_slice($topics, 0, $count);
    }

    /**
     * Queue topics for generation
     */
    public function queueTopics($campaign_id, $topics) {
        $campaign = $this->get($campaign_id);
        if (!$campaign) {
            return false;
        }

        $publishTimes = json_decode($campaign->publish_times, true) ?? ['09:00'];
        $currentDate = new DateTime();
        $queued = 0;

        foreach ($topics as $topic) {
            // Calculate scheduled time
            $scheduledTime = clone $currentDate;
            $scheduledTime->modify('+' . floor($queued / count($publishTimes)) . ' days');
            $scheduledTime->setTime(
                ...explode(':', $publishTimes[$queued % count($publishTimes)])
            );

            // Create queue item
            $this->db->insert('ai_queue', [
                'campaign_id' => $campaign_id,
                'topic' => $topic,
                'keywords' => json_encode(['primary' => [$topic]]),
                'status' => 'pending',
                'priority' => 5,
                'scheduled_for' => $scheduledTime->format('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $queued++;
        }

        return $queued;
    }

    /**
     * Get campaign statistics
     */
    public function getStats($campaign_id) {
        $stats = $this->db->queryOne("
            SELECT
                COUNT(*) as total_posts,
                SUM(views) as total_views,
                AVG(views) as avg_views
            FROM posts
            WHERE campaign_id = ?
        ", [$campaign_id]);

        $aiUsage = $this->db->queryOne("
            SELECT
                SUM(tokens_used) as total_tokens,
                SUM(cost) as total_cost
            FROM ai_usage
            WHERE campaign_id = ?
        ", [$campaign_id]);

        return [
            'posts' => $stats,
            'ai_usage' => $aiUsage
        ];
    }

    /**
     * Pause campaign
     */
    public function pause($id) {
        return $this->update($id, ['status' => 'paused']);
    }

    /**
     * Resume campaign
     */
    public function resume($id) {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Generate content immediately (manual trigger)
     */
    public function generateNow($campaign_id, $topic = null) {
        $campaign = $this->get($campaign_id);
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found'];
        }

        // If no topic provided, generate one
        if (!$topic) {
            $topics = $this->generateTopics($campaign_id, 1);
            $topic = $topics[0] ?? 'General Article about ' . $campaign->niche;
        }

        // Queue immediately (scheduled for now)
        $queueId = $this->db->insert('ai_queue', [
            'campaign_id' => $campaign_id,
            'topic' => $topic,
            'keywords' => json_encode(['primary' => [$topic]]),
            'status' => 'pending',
            'priority' => 10, // High priority for manual generation
            'scheduled_for' => date('Y-m-d H:i:s'), // Schedule for now
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'success' => true,
            'message' => 'Content generation queued',
            'queue_id' => $queueId,
            'topic' => $topic
        ];
    }
}
