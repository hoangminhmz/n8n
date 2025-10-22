<?php
/**
 * LightBlog CMS - Campaign Manager
 * Manages AI auto-blogging campaigns
 */

class Campaign {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
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
     * Generate topics for campaign
     */
    public function generateTopics($campaign_id, $count = 10) {
        $campaign = $this->get($campaign_id);
        if (!$campaign) {
            return [];
        }

        $seedKeywords = json_decode($campaign->seed_keywords, true) ?? [];
        $topics = [];

        // Simple topic generation (in production, use AI or keyword research APIs)
        $templates = [
            'How to {keyword}',
            'Best {keyword} in ' . date('Y'),
            '{keyword}: Complete Guide',
            '{keyword} Tips and Tricks',
            'Top 10 {keyword}',
            '{keyword} for Beginners',
            'Advanced {keyword} Strategies',
            '{keyword} vs Alternatives',
            'Why {keyword} Matters',
            '{keyword}: Expert Review'
        ];

        foreach ($seedKeywords as $keyword) {
            foreach ($templates as $template) {
                if (count($topics) >= $count) break 2;
                $topics[] = str_replace('{keyword}', $keyword, $template);
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
