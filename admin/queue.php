<?php
/**
 * LightBlog CMS - AI Generation Queue
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Generation Queue';
$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$campaignFilter = $_GET['campaign'] ?? null;

// Build query
$query = "SELECT q.*, c.name as campaign_name FROM ai_queue q
          LEFT JOIN campaigns c ON q.campaign_id = c.id";
$params = [];

if ($campaignFilter) {
    $query .= " WHERE q.campaign_id = ?";
    $params[] = $campaignFilter;
}

$query .= " ORDER BY q.created_at DESC LIMIT 100";

$queueItems = $db->query($query, $params);

// Get statistics
$stats = $db->queryOne("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM ai_queue
");

include __DIR__ . '/includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
    <div class="stat-card">
        <div class="stat-label">Total</div>
        <div class="stat-value"><?= $stats->total ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= $stats->pending ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Processing</div>
        <div class="stat-value"><?= $stats->processing ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?= $stats->completed ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value"><?= $stats->failed ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">AI Generation Queue</h2>
        <div style="display: flex; gap: 0.5rem;">
            <?php if ($stats->pending > 0): ?>
                <a href="<?= BASE_PATH ?>process-queue.php" class="btn btn-primary" target="_blank">⚡ Process Queue Now</a>
            <?php endif; ?>
            <?php if ($campaignFilter): ?>
                <a href="?campaign=" class="btn btn-outline">Show All</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($stats->pending > 0): ?>
        <div class="alert alert-warning">
            <strong>⏳ <?= $stats->pending ?> items pending</strong><br>
            Queue items need to be processed. Options:
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <li><strong>Instant:</strong> <a href="<?= BASE_PATH ?>process-queue.php" target="_blank">Click here to process now</a></li>
                <li><strong>Automatic:</strong> Set up cron job: <code>*/15 * * * * php <?= SITE_PATH ?>/cron.php</code></li>
                <li><strong>Manual:</strong> Visit <code><?= SITE_URL ?><?= BASE_PATH ?>cron.php</code> periodically</li>
            </ul>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            💡 Queue items are automatically processed by the cron job. Run <code>php cron.php</code> manually or set up automated cron.
        </div>
    <?php endif; ?>

    <?php if (empty($queueItems)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">⏱️</div>
            <h3>No Queue Items</h3>
            <p>Generate topics from your campaigns to see them here</p>
            <a href="/admin/campaigns.php" class="btn btn-primary">Go to Campaigns</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Scheduled</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queueItems as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item->topic) ?></strong>
                                <?php if ($item->error_message): ?>
                                    <br><small style="color: var(--danger);"><?= htmlspecialchars($item->error_message) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item->campaign_name ?? 'N/A') ?></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'danger'
                                ];
                                ?>
                                <span class="badge badge-<?= $statusColors[$item->status] ?? 'info' ?>">
                                    <?= $item->status ?>
                                </span>
                            </td>
                            <td><?= $item->priority ?></td>
                            <td>
                                <?= $item->scheduled_for ? date('M j, H:i', strtotime($item->scheduled_for)) : 'ASAP' ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($item->created_at)) ?></td>
                            <td>
                                <?php if ($item->generated_post_id): ?>
                                    <a href="/admin/posts.php?action=edit&id=<?= $item->generated_post_id ?>" class="btn btn-sm btn-outline">View Post</a>
                                <?php elseif ($item->status === 'failed'): ?>
                                    <span style="color: var(--text-light);">Failed</span>
                                <?php elseif ($item->status === 'pending'): ?>
                                    <span style="color: var(--text-light);">Waiting...</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
