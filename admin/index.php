<?php
/**
 * LightBlog CMS - Admin Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Dashboard';
$db = Database::getInstance();

// Get statistics
$stats = [
    'total_posts' => $db->count('posts'),
    'published_posts' => $db->count('posts', 'status = ?', ['published']),
    'draft_posts' => $db->count('posts', 'status = ?', ['draft']),
    'total_views' => $db->queryOne("SELECT SUM(views) as total FROM posts")->total ?? 0,
    'ai_generated' => $db->count('posts', 'is_ai_generated = ?', [1]),
    'active_campaigns' => $db->count('campaigns', 'status = ?', ['active']),
    'pending_queue' => $db->count('ai_queue', 'status = ?', ['pending']),
];

// Get recent posts
$recentPosts = $db->query("
    SELECT * FROM posts
    ORDER BY created_at DESC
    LIMIT 5
");

// Get AI usage this month
$aiUsageThisMonth = $db->queryOne("
    SELECT
        COUNT(*) as requests,
        SUM(tokens_used) as tokens,
        SUM(cost) as cost
    FROM ai_usage
    WHERE timestamp >= DATE('now', 'start of month')
");

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Posts</div>
        <div class="stat-value"><?= number_format($stats['total_posts']) ?></div>
        <div class="stat-change positive">
            📝 <?= $stats['published_posts'] ?> published
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Views</div>
        <div class="stat-value"><?= number_format($stats['total_views']) ?></div>
        <div class="stat-change">
            👁️ All time
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">AI Generated</div>
        <div class="stat-value"><?= number_format($stats['ai_generated']) ?></div>
        <div class="stat-change">
            🤖 <?= $stats['active_campaigns'] ?> active campaigns
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">AI Cost (Month)</div>
        <div class="stat-value">$<?= number_format($aiUsageThisMonth->cost ?? 0, 2) ?></div>
        <div class="stat-change">
            ⚡ <?= number_format($aiUsageThisMonth->tokens ?? 0) ?> tokens
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Quick Actions</h2>
    </div>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="<?= BASE_PATH ?>admin/posts.php?action=new" class="btn btn-primary">
            ✏️ New Post
        </a>
        <a href="<?= BASE_PATH ?>admin/campaigns.php?action=new" class="btn btn-success">
            🤖 New Campaign
        </a>
        <a href="<?= BASE_PATH ?>admin/queue.php" class="btn btn-outline">
            ⏱️ View Queue (<?= $stats['pending_queue'] ?>)
        </a>
        <a href="<?= BASE_PATH ?>admin/settings.php" class="btn btn-outline">
            ⚙️ Settings
        </a>
    </div>
</div>

<!-- Recent Posts -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Posts</h2>
        <a href="<?= BASE_PATH ?>admin/posts.php" class="btn btn-sm btn-outline">View All →</a>
    </div>

    <?php if (empty($recentPosts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <h3>No Posts Yet</h3>
            <p>Start creating content or set up an AI campaign</p>
            <a href="<?= BASE_PATH ?>admin/posts.php?action=new" class="btn btn-primary">Create Your First Post</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPosts as $post): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($post->title) ?></strong>
                            </td>
                            <td>
                                <?php
                                $statusColors = [
                                    'published' => 'success',
                                    'draft' => 'warning',
                                    'scheduled' => 'info'
                                ];
                                $badgeClass = $statusColors[$post->status] ?? 'info';
                                ?>
                                <span class="badge badge-<?= $badgeClass ?>">
                                    <?= $post->status ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($post->is_ai_generated): ?>
                                    <span class="badge badge-info">🤖 AI</span>
                                <?php else: ?>
                                    <span class="badge">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($post->views) ?></td>
                            <td><?= date('M j, Y', strtotime($post->created_at)) ?></td>
                            <td>
                                <a href="<?= BASE_PATH ?>admin/posts.php?action=edit&id=<?= $post->id ?>" class="btn btn-sm btn-outline">Edit</a>
                                <?php if ($post->status === 'published'): ?>
                                    <a href="<?= BASE_PATH ?>post/<?= $post->slug ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- AI Campaigns Overview (if enabled) -->
<?php if (defined('AUTOBLOG_ENABLED') && AUTOBLOG_ENABLED && $stats['active_campaigns'] > 0): ?>
    <?php
    $campaigns = $db->query("
        SELECT c.*, COUNT(p.id) as posts_count
        FROM campaigns c
        LEFT JOIN posts p ON c.id = p.campaign_id
        WHERE c.status = 'active'
        GROUP BY c.id
        LIMIT 3
    ");
    ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Active AI Campaigns</h2>
            <a href="<?= BASE_PATH ?>admin/campaigns.php" class="btn btn-sm btn-outline">View All →</a>
        </div>

        <div style="display: grid; gap: 1rem;">
            <?php foreach ($campaigns as $campaign): ?>
                <div style="padding: 1rem; background: var(--bg); border-radius: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h3 style="margin-bottom: 0.5rem;">
                                🤖 <?= htmlspecialchars($campaign->name) ?>
                            </h3>
                            <p style="color: var(--text-light); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($campaign->niche) ?> •
                                <?= $campaign->ai_provider ?>/<?= $campaign->ai_model ?>
                            </p>
                            <div>
                                <span class="badge badge-success">
                                    <?= $campaign->posts_count ?> / <?= $campaign->target_count ?> posts
                                </span>
                            </div>
                        </div>
                        <a href="<?= BASE_PATH ?>admin/campaigns.php?id=<?= $campaign->id ?>" class="btn btn-sm btn-outline">
                            Manage →
                        </a>
                    </div>

                    <div class="progress" style="margin-top: 1rem;">
                        <div class="progress-bar" style="width: <?= ($campaign->posts_count / $campaign->target_count) * 100 ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
