<?php
/**
 * LightBlog CMS - Post Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Posts';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$action = $_GET['action'] ?? 'list';
$postId = $_GET['id'] ?? null;
$message = '';
$messageType = 'success';
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action']) && !empty($_POST['selected_posts'])) {
        // Bulk actions
        $bulkAction = $_POST['bulk_action'];
        $postIds = $_POST['selected_posts'];
        $count = 0;

        switch ($bulkAction) {
            case 'delete':
                foreach ($postIds as $id) {
                    $db->delete('posts', 'id = ?', [(int)$id]);
                    $count++;
                }
                $message = "✅ Deleted {$count} post(s)";
                break;

            case 'publish':
                foreach ($postIds as $id) {
                    $db->update('posts', [
                        'status' => 'published',
                        'published_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', ['id' => (int)$id]);
                    $count++;
                }
                $message = "✅ Published {$count} post(s)";
                break;

            case 'draft':
                foreach ($postIds as $id) {
                    $db->update('posts', [
                        'status' => 'draft'
                    ], 'id = :id', ['id' => (int)$id]);
                    $count++;
                }
                $message = "✅ Changed {$count} post(s) to draft";
                break;
        }
        $action = 'list';
    } elseif (isset($_POST['delete'])) {
        // Delete post
        $db->delete('posts', 'id = ?', [$_POST['post_id']]);
        $message = 'Post deleted successfully';
        $action = 'list';
    } elseif (isset($_POST['save'])) {
        // Save post
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $_POST['title'])));

        // Check if slug exists
        $existingPost = $db->queryOne("SELECT id FROM posts WHERE slug = ? AND id != ?", [
            $slug,
            $_POST['post_id'] ?? 0
        ]);

        if ($existingPost) {
            $slug .= '-' . time();
        }

        $data = [
            'title' => $_POST['title'],
            'slug' => $slug,
            'content' => $_POST['content'],
            'excerpt' => $_POST['excerpt'],
            'status' => $_POST['status'],
            'meta_description' => $_POST['meta_description'] ?? '',
            'seo_title' => $_POST['seo_title'] ?? $_POST['title'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($postId) {
            // Update
            $db->update('posts', $data, 'id = :id', ['id' => $postId]);
            $message = 'Post updated successfully';
        } else {
            // Create
            $data['author_id'] = $auth->getCurrentUserId();
            $data['created_at'] = date('Y-m-d H:i:s');
            if ($data['status'] === 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            $postId = $db->insert('posts', $data);
            $message = 'Post created successfully';
        }
    }
}

// Get post data for edit
$post = null;
if ($postId && in_array($action, ['edit', 'view'])) {
    $post = $db->queryOne("SELECT * FROM posts WHERE id = ?", [$postId]);
}

// Get all posts for list
$posts = [];
if ($action === 'list') {
    $posts = $db->query("
        SELECT p.*, u.username
        FROM posts p
        LEFT JOIN users u ON p.author_id = u.id
        ORDER BY p.created_at DESC
    ");
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Post List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Posts</h2>
            <a href="?action=new" class="btn btn-primary">+ New Post</a>
        </div>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <h3>No Posts Yet</h3>
                <p>Create your first post to get started</p>
                <a href="?action=new" class="btn btn-primary">Create Post</a>
            </div>
        <?php else: ?>
            <form method="POST" id="bulkForm">
                <!-- Bulk Actions Bar -->
                <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <select name="bulk_action" id="bulkAction" class="form-control" style="width: 200px;">
                        <option value="">Bulk Actions</option>
                        <option value="publish">Publish</option>
                        <option value="draft">Set to Draft</option>
                        <option value="delete">Delete</option>
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
                                <th>Title</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Views</th>
                                <th>Date</th>
                                <th style="width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_posts[]" value="<?= $p->id ?>" class="post-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p->title) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($p->username ?? 'Unknown') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p->status === 'published' ? 'success' : 'warning' ?>">
                                            <?= $p->status ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p->is_ai_generated): ?>
                                            <span class="badge badge-info">🤖 AI</span>
                                        <?php else: ?>
                                            <span class="badge">Manual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($p->views) ?></td>
                                    <td><?= date('M j, Y', strtotime($p->created_at)) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <a href="?action=edit&id=<?= $p->id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                            <?php if ($p->status === 'published'): ?>
                                                <a href="<?= SITE_URL ?><?= BASE_PATH ?>post/<?= $p->slug ?>" target="_blank" class="btn btn-sm btn-outline">👁️ View</a>
                                            <?php endif; ?>
                                            <form method="POST" style="display:inline; margin: 0;" onsubmit="return confirm('Delete this post?');">
                                                <input type="hidden" name="post_id" value="<?= $p->id ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-danger">🗑️</button>
                                            </form>
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
                    const checkboxes = document.querySelectorAll('.post-checkbox');
                    checkboxes.forEach(cb => cb.checked = checkbox.checked);
                    updateSelectedCount();
                }

                function updateSelectedCount() {
                    const checked = document.querySelectorAll('.post-checkbox:checked').length;
                    const total = document.querySelectorAll('.post-checkbox').length;
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
                    const checked = document.querySelectorAll('.post-checkbox:checked').length;

                    if (!action) {
                        alert('Please select an action from the dropdown.');
                        return false;
                    }

                    if (checked === 0) {
                        alert('Please select at least one post.');
                        return false;
                    }

                    const actionNames = {
                        'delete': 'delete',
                        'publish': 'publish',
                        'draft': 'set to draft'
                    };

                    return confirm(`Are you sure you want to ${actionNames[action]} ${checked} post(s)?`);
                }

                // Initialize count on page load
                document.addEventListener('DOMContentLoaded', updateSelectedCount);
            </script>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <!-- Post Editor -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $action === 'new' ? 'Create New Post' : 'Edit Post' ?></h2>
            <a href="?action=list" class="btn btn-outline">← Back to Posts</a>
        </div>

        <form method="POST">
            <?php if ($postId): ?>
                <input type="hidden" name="post_id" value="<?= $postId ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($post->title ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" rows="20" required><?= htmlspecialchars($post->content ?? '') ?></textarea>
                <small>You can use HTML tags for formatting</small>
            </div>

            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($post->excerpt ?? '') ?></textarea>
                <small>Short description for archive pages</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status">
                        <option value="draft" <?= ($post->status ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($post->status ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="seo_title">SEO Title</label>
                    <input type="text" id="seo_title" name="seo_title" value="<?= htmlspecialchars($post->seo_title ?? $post->title ?? '') ?>">
                    <small>Recommended: 50-60 characters</small>
                </div>
            </div>

            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea id="meta_description" name="meta_description" rows="2"><?= htmlspecialchars($post->meta_description ?? '') ?></textarea>
                <small>Recommended: 150-160 characters</small>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" name="save" class="btn btn-primary">
                    <?= $action === 'new' ? 'Create Post' : 'Update Post' ?>
                </button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
