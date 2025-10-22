<?php
/**
 * LightBlog CMS - Content Scheduler
 * Handles scheduling and publishing of posts
 */

class Scheduler {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Publish scheduled posts
     */
    public function publishScheduled() {
        $posts = $this->db->query("
            SELECT * FROM posts
            WHERE status = 'scheduled'
            AND published_at <= ?
        ", [date('Y-m-d H:i:s')]);

        $published = 0;

        foreach ($posts as $post) {
            $this->db->update('posts',
                ['status' => 'published'],
                'id = :id',
                ['id' => $post->id]
            );
            $published++;
        }

        return $published;
    }

    /**
     * Schedule a post
     */
    public function schedule($post_id, $publish_at) {
        return $this->db->update('posts',
            [
                'status' => 'scheduled',
                'published_at' => $publish_at
            ],
            'id = :id',
            ['id' => $post_id]
        );
    }

    /**
     * Get next available publish time for campaign
     */
    public function getNextPublishTime($campaign_id) {
        $campaign = $this->db->queryOne("SELECT * FROM campaigns WHERE id = ?", [$campaign_id]);
        if (!$campaign) {
            return null;
        }

        $publishTimes = json_decode($campaign->publish_times, true) ?? ['09:00'];
        $postsPerDay = $campaign->posts_per_day ?? count($publishTimes);

        // Get last scheduled post for this campaign
        $lastPost = $this->db->queryOne("
            SELECT MAX(published_at) as last_publish
            FROM posts
            WHERE campaign_id = ?
        ", [$campaign_id]);

        $now = new DateTime();
        $lastPublish = $lastPost && $lastPost->last_publish
            ? new DateTime($lastPost->last_publish)
            : $now;

        // If last publish was today and we've reached the limit, move to tomorrow
        $todayPosts = $this->db->count('posts',
            'campaign_id = ? AND DATE(published_at) = ?',
            [$campaign_id, $now->format('Y-m-d')]
        );

        if ($todayPosts >= $postsPerDay) {
            $now->modify('+1 day');
        }

        // Find next available time slot
        $currentHour = (int)$now->format('H');
        $nextTime = null;

        foreach ($publishTimes as $time) {
            list($hour, $minute) = explode(':', $time);
            if ((int)$hour > $currentHour) {
                $nextTime = $time;
                break;
            }
        }

        // If no time found today, use first time tomorrow
        if (!$nextTime) {
            $now->modify('+1 day');
            $nextTime = $publishTimes[0];
        }

        list($hour, $minute) = explode(':', $nextTime);
        $now->setTime((int)$hour, (int)$minute, 0);

        return $now->format('Y-m-d H:i:s');
    }
}
