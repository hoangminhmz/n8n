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
$message = '';
$messageType = 'success';

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action']) && !empty($_POST['selected_items'])) {
        $action = $_POST['bulk_action'];
        $items = $_POST['selected_items'];
        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($items as $id) {
                    $db->delete('ai_queue', 'id = ?', [(int)$id]);
                    $count++;
                }
                $message = "✅ Deleted {$count} queue item(s)";
                break;

            case 'process':
                foreach ($items as $id) {
                    $item = $db->queryOne("SELECT * FROM ai_queue WHERE id = ?", [(int)$id]);
                    if ($item && ($item->status === 'pending' || $item->status === 'failed')) {
                        $db->update('ai_queue', [
                            'status' => 'pending',
                            'scheduled_for' => date('Y-m-d H:i:s'),
                            'priority' => 10,
                            'error_message' => null
                        ], 'id = :id', ['id' => (int)$id]);
                        $count++;
                    }
                }
                $message = "✅ Queued {$count} item(s) for immediate processing. <a href='" . BASE_PATH . "process-queue.php' target='_blank'>Click here to process now</a>";
                break;

            case 'retry':
                foreach ($items as $id) {
                    $item = $db->queryOne("SELECT * FROM ai_queue WHERE id = ?", [(int)$id]);
                    if ($item && $item->status === 'failed') {
                        $db->update('ai_queue', [
                            'status' => 'pending',
                            'scheduled_for' => date('Y-m-d H:i:s'),
                            'error_message' => null
                        ], 'id = :id', ['id' => (int)$id]);
                        $count++;
                    }
                }
                $message = "✅ Retrying {$count} failed item(s)";
                break;
        }
    } elseif (isset($_POST['delete_item'])) {
        $db->delete('ai_queue', 'id = ?', [(int)$_POST['item_id']]);
        $message = "✅ Queue item deleted";
    } elseif (isset($_POST['process_item'])) {
        $db->update('ai_queue', [
            'status' => 'pending',
            'scheduled_for' => date('Y-m-d H:i:s'),
            'priority' => 10,
            'error_message' => null
        ], 'id = :id', ['id' => (int)$_POST['item_id']]);
        $message = "✅ Item queued for immediate processing. <a href='" . BASE_PATH . "process-queue.php' target='_blank'>Click here to process now</a>";
    } elseif (isset($_POST['retry_item'])) {
        $db->update('ai_queue', [
            'status' => 'pending',
            'scheduled_for' => date('Y-m-d H:i:s'),
            'error_message' => null
        ], 'id = :id', ['id' => (int)$_POST['item_id']]);
        $message = "✅ Item retry scheduled";
    }
}

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

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

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
            <a href="<?= $basePath ?>admin/campaigns.php" class="btn btn-primary">Go to Campaigns</a>
        </div>
    <?php else: ?>
        <form method="POST" id="bulkForm">
            <!-- Bulk Actions Bar -->
            <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                <select name="bulk_action" id="bulkAction" class="form-control" style="width: 200px;">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="process">Process Now</option>
                    <option value="retry">Retry Failed</option>
                </select>
                <button type="submit" class="btn btn-primary" onclick="return confirmBulkAction()">Apply</button>
                <span id="selectedCount" style="color: #6b7280; font-size: 0.875rem;"></span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                            </th>
                            <th>Topic</th>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Scheduled</th>
                            <th>Created</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queueItems as $item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_items[]" value="<?= $item->id ?>" class="item-checkbox" onchange="updateSelectedCount()">
                                </td>
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
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <?php if ($item->generated_post_id): ?>
                                            <a href="<?= BASE_PATH ?>admin/posts.php?action=edit&id=<?= $item->generated_post_id ?>" class="btn btn-sm btn-outline">📄 View Post</a>
                                        <?php elseif ($item->status === 'failed'): ?>
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="item_id" value="<?= $item->id ?>">
                                                <button type="submit" name="retry_item" class="btn btn-sm btn-warning" title="Retry">🔄 Retry</button>
                                            </form>
                                        <?php elseif ($item->status === 'pending'): ?>
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="item_id" value="<?= $item->id ?>">
                                                <button type="submit" name="process_item" class="btn btn-sm btn-success" title="Process Now">⚡ Process</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($item->status !== 'processing'): ?>
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="item_id" value="<?= $item->id ?>">
                                                <button type="submit" name="delete_item" class="btn btn-sm btn-danger" onclick="return confirm('Delete this queue item?')" title="Delete">🗑</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <script>
            function toggleSelectAll(checkbox) {
                const checkboxes = document.querySelectorAll('.item-checkbox');
                checkboxes.forEach(cb => cb.checked = checkbox.checked);
                updateSelectedCount();
            }

            function updateSelectedCount() {
                const checked = document.querySelectorAll('.item-checkbox:checked').length;
                const total = document.querySelectorAll('.item-checkbox').length;
                const countEl = document.getElementById('selectedCount');

                if (checked > 0) {
                    countEl.textContent = `${checked} of ${total} selected`;
                    countEl.style.fontWeight = '600';
                    countEl.style.color = '#3b82f6';
                } else {
                    countEl.textContent = '';
                }

                // Update select all checkbox state
                const selectAllCheckbox = document.getElementById('selectAll');
                selectAllCheckbox.checked = checked === total && total > 0;
                selectAllCheckbox.indeterminate = checked > 0 && checked < total;
            }

            function confirmBulkAction() {
                const action = document.getElementById('bulkAction').value;
                const checked = document.querySelectorAll('.item-checkbox:checked').length;

                if (!action) {
                    alert('Please select an action from the dropdown.');
                    return false;
                }

                if (checked === 0) {
                    alert('Please select at least one item.');
                    return false;
                }

                const actionNames = {
                    'delete': 'delete',
                    'process': 'process now',
                    'retry': 'retry'
                };

                return confirm(`Are you sure you want to ${actionNames[action]} ${checked} item(s)?`);
            }

            // Initialize count on page load
            document.addEventListener('DOMContentLoaded', updateSelectedCount);
        </script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
