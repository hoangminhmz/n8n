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
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
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
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
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
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Views</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                            <tr>
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
                                    <a href="?action=edit&id=<?= $p->id ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <?php if ($p->status === 'published'): ?>
                                        <a href="/post/<?= $p->slug ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                                        <input type="hidden" name="post_id" value="<?= $p->id ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
