<?php
/**
 * LightBlog CMS - Post Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';
require_once SITE_PATH . '/core/SEO/SEOAnalyzer.php';

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
        // Fix: Convert to lowercase BEFORE regex to preserve letters
        $slug = strtolower($_POST['title']);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

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
            'featured_image' => $_POST['featured_image'] ?? '',
            'status' => $_POST['status'],
            'meta_description' => $_POST['meta_description'] ?? '',
            'seo_title' => $_POST['seo_title'] ?? $_POST['title'],
            'updated_at' => date('Y-m-d H:i:s'),
            // SEO Meta
            'focus_keyword' => $_POST['focus_keyword'] ?? '',
            'canonical_url' => $_POST['canonical_url'] ?? '',
            'meta_robots' => $_POST['meta_robots'] ?? 'index,follow',
            // Open Graph
            'og_title' => $_POST['og_title'] ?? '',
            'og_description' => $_POST['og_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            // Twitter Cards
            'twitter_title' => $_POST['twitter_title'] ?? '',
            'twitter_description' => $_POST['twitter_description'] ?? '',
            'twitter_image' => $_POST['twitter_image'] ?? '',
            // Schema
            'schema_type' => $_POST['schema_type'] ?? 'Article',
            'faq_data' => $_POST['faq_data'] ?? ''
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

        // Save categories
        $selectedCategories = $_POST['categories'] ?? [];
        // Delete existing category associations
        $db->query("DELETE FROM post_categories WHERE post_id = ?", [$postId]);
        // Insert new category associations
        foreach ($selectedCategories as $categoryId) {
            $db->query("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)", [$postId, (int)$categoryId]);
        }

        // Recalculate SEO metrics after save
        $savedPost = $db->queryOne("SELECT * FROM posts WHERE id = ?", [$postId]);
        $seoAnalyzer = new SEOAnalyzer();
        $seoMetrics = $seoAnalyzer->analyze($savedPost, $savedPost->content);

        // Update SEO metrics
        $db->update('posts', [
            'word_count' => $seoMetrics['word_count'],
            'reading_time' => $seoMetrics['reading_time'],
            'readability_score' => $seoMetrics['readability_score'],
            'internal_links_count' => $seoMetrics['internal_links_count'],
            'external_links_count' => $seoMetrics['external_links_count'],
            'images_count' => $seoMetrics['images_count'],
            'has_table_of_contents' => $seoMetrics['has_table_of_contents'],
            'seo_score' => $seoMetrics['seo_score'],
            'last_seo_check' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $postId]);
    }
}

// Get post data for edit
$post = null;
$seoMetrics = null;
$seoRecommendations = [];
$postCategories = [];
if ($postId && in_array($action, ['edit', 'view'])) {
    $post = $db->queryOne("SELECT * FROM posts WHERE id = ?", [$postId]);

    // Get post categories
    $postCategories = $db->query("SELECT category_id FROM post_categories WHERE post_id = ?", [$postId]);
    $postCategories = array_column($postCategories, 'category_id');

    // Calculate SEO metrics and get recommendations
    if ($post && $post->content) {
        $seoAnalyzer = new SEOAnalyzer();
        $seoMetrics = $seoAnalyzer->analyze($post, $post->content);
        $seoRecommendations = $seoAnalyzer->getRecommendations($post, $post->content, $seoMetrics);
    }
}

// Get all categories for the form
$categories = [];
if (in_array($action, ['new', 'edit'])) {
    $categories = $db->query("SELECT * FROM categories ORDER BY name ASC");
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
                                <th>SEO</th>
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
                                    <td>
                                        <?php
                                        $seoScore = $p->seo_score ?? 0;
                                        $seoColor = 'secondary';
                                        $seoLabel = 'N/A';
                                        if ($seoScore > 0) {
                                            if ($seoScore >= 80) {
                                                $seoColor = 'success';
                                                $seoLabel = 'Excellent';
                                            } elseif ($seoScore >= 60) {
                                                $seoColor = 'info';
                                                $seoLabel = 'Good';
                                            } elseif ($seoScore >= 40) {
                                                $seoColor = 'warning';
                                                $seoLabel = 'Fair';
                                            } else {
                                                $seoColor = 'danger';
                                                $seoLabel = 'Poor';
                                            }
                                        }
                                        ?>
                                        <span class="badge badge-<?= $seoColor ?>" title="SEO Score: <?= $seoScore ?>/100">
                                            <?= $seoScore ?> - <?= $seoLabel ?>
                                        </span>
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

            <div class="form-group">
                <label for="featured_image">Featured Image / Thumbnail</label>
                <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                    <input type="url" id="featured_image" name="featured_image" value="<?= htmlspecialchars($post->featured_image ?? '') ?>" style="flex: 1;" placeholder="https://example.com/image.jpg">
                    <button type="button" id="generateThumbnail" class="btn btn-info" style="white-space: nowrap; display: flex; align-items: center; gap: 0.5rem;">
                        <span id="genThumbIcon">🎨</span>
                        <span id="genThumbText">Auto Generate</span>
                    </button>
                </div>
                <small>URL of the featured image for this post, or click "Auto Generate" to create one based on the title</small>
                <?php if (!empty($post->featured_image)): ?>
                    <div style="margin-top: 0.75rem;">
                        <img src="<?= htmlspecialchars($post->featured_image) ?>" alt="Preview" style="max-width: 300px; border-radius: 6px; border: 1px solid #e5e7eb;">
                    </div>
                <?php endif; ?>
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
                <label>Categories</label>
                <?php if (empty($categories)): ?>
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0.5rem 0;">
                        No categories available. <a href="categories.php" style="color: #3b82f6;">Create categories</a> first.
                    </p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <?php foreach ($categories as $cat): ?>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; background: white; border-radius: 4px; border: 1px solid #e5e7eb;">
                                <input type="checkbox" name="categories[]" value="<?= $cat->id ?>"
                                       <?= in_array($cat->id, $postCategories) ? 'checked' : '' ?>
                                       style="cursor: pointer;">
                                <span><?= htmlspecialchars($cat->name) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small>Select one or more categories for this post</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea id="meta_description" name="meta_description" rows="2" maxlength="160"><?= htmlspecialchars($post->meta_description ?? '') ?></textarea>
                <small>Recommended: 150-160 characters <span id="meta_desc_count"></span></small>
            </div>

            <?php if ($action === 'edit' && $seoMetrics): ?>
                <!-- SEO Dashboard -->
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin: 2rem 0;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600;">SEO Performance</h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <!-- SEO Score -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">SEO Score</div>
                            <div style="font-size: 2rem; font-weight: 700; color: <?= $seoMetrics['seo_score'] >= 80 ? '#10b981' : ($seoMetrics['seo_score'] >= 60 ? '#3b82f6' : ($seoMetrics['seo_score'] >= 40 ? '#f59e0b' : '#ef4444')) ?>;">
                                <?= $seoMetrics['seo_score'] ?><span style="font-size: 1rem; color: #6b7280;">/100</span>
                            </div>
                        </div>

                        <!-- Word Count -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Word Count</div>
                            <div style="font-size: 1.5rem; font-weight: 600;"><?= number_format($seoMetrics['word_count']) ?></div>
                            <div style="font-size: 0.75rem; color: #6b7280;"><?= $seoMetrics['reading_time'] ?> min read</div>
                        </div>

                        <!-- Readability -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Readability</div>
                            <div style="font-size: 1.5rem; font-weight: 600;"><?= round($seoMetrics['readability_score'], 1) ?></div>
                            <div style="font-size: 0.75rem; color: #6b7280;">
                                <?= $seoMetrics['readability_score'] >= 60 ? 'Easy to read' : ($seoMetrics['readability_score'] >= 30 ? 'Moderate' : 'Difficult') ?>
                            </div>
                        </div>

                        <!-- Links -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Links</div>
                            <div style="font-size: 1rem; font-weight: 600;">
                                🔗 <?= $seoMetrics['internal_links_count'] ?> internal<br>
                                🌐 <?= $seoMetrics['external_links_count'] ?> external
                            </div>
                        </div>

                        <!-- Images -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Images</div>
                            <div style="font-size: 1.5rem; font-weight: 600;">🖼️ <?= $seoMetrics['images_count'] ?></div>
                        </div>

                        <!-- TOC -->
                        <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">Table of Contents</div>
                            <div style="font-size: 1.5rem; font-weight: 600;">
                                <?= $seoMetrics['has_table_of_contents'] ? '✅ Yes' : '❌ No' ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($seoRecommendations)): ?>
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                            <h4 style="margin: 0 0 0.75rem 0; font-size: 1rem; font-weight: 600;">SEO Recommendations</h4>
                            <ul style="margin: 0; padding-left: 1.5rem; color: #374151;">
                                <?php foreach ($seoRecommendations as $rec): ?>
                                    <li style="margin-bottom: 0.5rem;"><?= htmlspecialchars($rec) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- SEO Fields Section -->
            <div style="border-top: 2px solid #e5e7eb; padding-top: 2rem; margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">Advanced SEO</h3>
                    <button type="button" id="autoFillSEO" class="btn btn-info" style="display: flex; align-items: center; gap: 0.5rem;">
                        <span id="autoFillIcon">🤖</span>
                        <span id="autoFillText">Auto-Fill SEO with AI</span>
                    </button>
                </div>

                <!-- Focus Keyword -->
                <div class="form-group">
                    <label for="focus_keyword">Focus Keyword</label>
                    <input type="text" id="focus_keyword" name="focus_keyword" value="<?= htmlspecialchars($post->focus_keyword ?? '') ?>">
                    <small>Main keyword you're targeting for this post</small>
                </div>

                <!-- Canonical URL -->
                <div class="form-group">
                    <label for="canonical_url">Canonical URL</label>
                    <input type="url" id="canonical_url" name="canonical_url" value="<?= htmlspecialchars($post->canonical_url ?? '') ?>">
                    <small>Leave blank to use default post URL</small>
                </div>

                <!-- Meta Robots -->
                <div class="form-group">
                    <label for="meta_robots">Meta Robots</label>
                    <select id="meta_robots" name="meta_robots">
                        <option value="index,follow" <?= ($post->meta_robots ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>Index, Follow (Default)</option>
                        <option value="noindex,follow" <?= ($post->meta_robots ?? '') === 'noindex,follow' ? 'selected' : '' ?>>No Index, Follow</option>
                        <option value="index,nofollow" <?= ($post->meta_robots ?? '') === 'index,nofollow' ? 'selected' : '' ?>>Index, No Follow</option>
                        <option value="noindex,nofollow" <?= ($post->meta_robots ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>No Index, No Follow</option>
                    </select>
                    <small>Control how search engines index this page</small>
                </div>

                <!-- Open Graph Section -->
                <h4 style="margin: 2rem 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #374151;">Open Graph (Facebook, LinkedIn)</h4>

                <div class="form-group">
                    <label for="og_title">OG Title</label>
                    <input type="text" id="og_title" name="og_title" value="<?= htmlspecialchars($post->og_title ?? '') ?>" placeholder="<?= htmlspecialchars($post->title ?? '') ?>">
                    <small>Leave blank to use post title</small>
                </div>

                <div class="form-group">
                    <label for="og_description">OG Description</label>
                    <textarea id="og_description" name="og_description" rows="2" maxlength="200"><?= htmlspecialchars($post->og_description ?? '') ?></textarea>
                    <small>Recommended: 150-200 characters</small>
                </div>

                <div class="form-group">
                    <label for="og_image">OG Image URL</label>
                    <input type="url" id="og_image" name="og_image" value="<?= htmlspecialchars($post->og_image ?? '') ?>">
                    <small>Recommended: 1200x630px for best display</small>
                </div>

                <!-- Twitter Card Section -->
                <h4 style="margin: 2rem 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #374151;">Twitter Card</h4>

                <div class="form-group">
                    <label for="twitter_title">Twitter Title</label>
                    <input type="text" id="twitter_title" name="twitter_title" value="<?= htmlspecialchars($post->twitter_title ?? '') ?>" placeholder="<?= htmlspecialchars($post->title ?? '') ?>">
                    <small>Leave blank to use post title</small>
                </div>

                <div class="form-group">
                    <label for="twitter_description">Twitter Description</label>
                    <textarea id="twitter_description" name="twitter_description" rows="2" maxlength="200"><?= htmlspecialchars($post->twitter_description ?? '') ?></textarea>
                    <small>Recommended: 150-200 characters</small>
                </div>

                <div class="form-group">
                    <label for="twitter_image">Twitter Image URL</label>
                    <input type="url" id="twitter_image" name="twitter_image" value="<?= htmlspecialchars($post->twitter_image ?? '') ?>">
                    <small>Recommended: 1200x675px or 1:1 ratio</small>
                </div>

                <!-- Schema Section -->
                <h4 style="margin: 2rem 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #374151;">Structured Data</h4>

                <div class="form-group">
                    <label for="schema_type">Schema Type</label>
                    <select id="schema_type" name="schema_type">
                        <option value="Article" <?= ($post->schema_type ?? 'Article') === 'Article' ? 'selected' : '' ?>>Article (Default)</option>
                        <option value="BlogPosting" <?= ($post->schema_type ?? '') === 'BlogPosting' ? 'selected' : '' ?>>Blog Posting</option>
                        <option value="NewsArticle" <?= ($post->schema_type ?? '') === 'NewsArticle' ? 'selected' : '' ?>>News Article</option>
                        <option value="HowTo" <?= ($post->schema_type ?? '') === 'HowTo' ? 'selected' : '' ?>>How-To Guide</option>
                        <option value="FAQPage" <?= ($post->schema_type ?? '') === 'FAQPage' ? 'selected' : '' ?>>FAQ Page</option>
                        <option value="Review" <?= ($post->schema_type ?? '') === 'Review' ? 'selected' : '' ?>>Review</option>
                    </select>
                    <small>Schema.org type for structured data</small>
                </div>

                <div class="form-group">
                    <label for="faq_data">FAQ Data (JSON)</label>
                    <textarea id="faq_data" name="faq_data" rows="4" style="font-family: monospace; font-size: 0.875rem;"><?= htmlspecialchars($post->faq_data ?? '') ?></textarea>
                    <small>Auto-detected FAQ data in JSON format. Edit manually if needed.</small>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" name="save" class="btn btn-primary">
                    <?= $action === 'new' ? 'Create Post' : 'Update Post' ?>
                </button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Character counters and Auto-fill SEO -->
    <script>
        function updateCharCount(textareaId, countSpanId, limit) {
            const textarea = document.getElementById(textareaId);
            const countSpan = document.getElementById(countSpanId);
            if (!textarea || !countSpan) return;

            const update = () => {
                const length = textarea.value.length;
                countSpan.textContent = `(${length}/${limit})`;
                countSpan.style.color = length > limit ? '#ef4444' : (length >= limit - 10 ? '#f59e0b' : '#10b981');
            };

            textarea.addEventListener('input', update);
            update();
        }

        // Auto-fill SEO with AI
        document.getElementById('autoFillSEO')?.addEventListener('click', async function(e) {
            e.preventDefault();

            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;
            const canonicalUrl = document.getElementById('canonical_url').value;

            if (!title || !content) {
                alert('Please enter title and content first before auto-filling SEO fields.');
                return;
            }

            // Show loading state
            const btn = this;
            const icon = document.getElementById('autoFillIcon');
            const text = document.getElementById('autoFillText');
            const originalText = text.textContent;

            btn.disabled = true;
            icon.textContent = '⏳';
            text.textContent = 'Generating SEO data...';

            try {
                const response = await fetch('ajax-autofill-seo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        title: title,
                        content: content,
                        postUrl: canonicalUrl
                    })
                });

                // Check if response is OK
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server error response:', errorText);
                    throw new Error('Server error: ' + response.status);
                }

                // Try to parse JSON
                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    const responseText = await response.text();
                    console.error('Invalid JSON response:', responseText);
                    throw new Error('Invalid server response. Check browser console for details.');
                }

                if (!result.success) {
                    console.error('API error:', result);
                    throw new Error(result.error || 'Failed to generate SEO data');
                }

                // Fill in all SEO fields
                const data = result.data;

                document.getElementById('focus_keyword').value = data.focus_keyword || '';
                document.getElementById('seo_title').value = data.seo_title || '';
                document.getElementById('meta_description').value = data.meta_description || '';
                document.getElementById('canonical_url').value = data.canonical_url || '';
                document.getElementById('meta_robots').value = data.meta_robots || 'index,follow';

                document.getElementById('og_title').value = data.og_title || '';
                document.getElementById('og_description').value = data.og_description || '';
                document.getElementById('og_image').value = data.og_image || '';

                document.getElementById('twitter_title').value = data.twitter_title || '';
                document.getElementById('twitter_description').value = data.twitter_description || '';
                document.getElementById('twitter_image').value = data.twitter_image || '';

                document.getElementById('schema_type').value = data.schema_type || 'Article';
                document.getElementById('faq_data').value = data.faq_data || '';

                // Update character counts
                updateCharCount('meta_description', 'meta_desc_count', 160);

                // Success feedback
                icon.textContent = '✅';
                text.textContent = 'SEO fields auto-filled!';

                setTimeout(() => {
                    icon.textContent = '🤖';
                    text.textContent = originalText;
                    btn.disabled = false;
                }, 2000);

            } catch (error) {
                console.error('Auto-fill SEO error:', error);
                alert('Error: ' + error.message);

                icon.textContent = '❌';
                text.textContent = 'Failed to auto-fill';

                setTimeout(() => {
                    icon.textContent = '🤖';
                    text.textContent = originalText;
                    btn.disabled = false;
                }, 2000);
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            updateCharCount('meta_description', 'meta_desc_count', 160);
        });

        // Auto-generate thumbnail
        document.getElementById('generateThumbnail')?.addEventListener('click', async function(e) {
            e.preventDefault();

            const title = document.getElementById('title').value;

            if (!title) {
                alert('Please enter a title first before generating a thumbnail.');
                return;
            }

            // Show loading state
            const btn = this;
            const icon = document.getElementById('genThumbIcon');
            const text = document.getElementById('genThumbText');
            const originalText = text.textContent;

            btn.disabled = true;
            icon.textContent = '⏳';
            text.textContent = 'Generating...';

            try {
                const response = await fetch('ajax-generate-thumbnail.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        title: title
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server error response:', errorText);
                    throw new Error('Server error: ' + response.status);
                }

                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    const responseText = await response.text();
                    console.error('Invalid JSON response:', responseText);
                    throw new Error('Invalid server response. Check browser console for details.');
                }

                if (!result.success) {
                    console.error('API error:', result);
                    throw new Error(result.error || 'Failed to generate thumbnail');
                }

                // Update the featured image URL
                document.getElementById('featured_image').value = result.image_url;

                // Show preview
                const existingPreview = document.querySelector('#featured_image').parentElement.parentElement.querySelector('img');
                if (existingPreview) {
                    existingPreview.src = result.image_url;
                } else {
                    const previewDiv = document.createElement('div');
                    previewDiv.style.marginTop = '0.75rem';
                    previewDiv.innerHTML = `<img src="${result.image_url}" alt="Preview" style="max-width: 300px; border-radius: 6px; border: 1px solid #e5e7eb;">`;
                    document.querySelector('#featured_image').parentElement.parentElement.appendChild(previewDiv);
                }

                // Success feedback
                icon.textContent = '✅';
                text.textContent = 'Thumbnail generated!';

                setTimeout(() => {
                    icon.textContent = '🎨';
                    text.textContent = originalText;
                    btn.disabled = false;
                }, 2000);

            } catch (error) {
                console.error('Generate thumbnail error:', error);
                alert('Error: ' + error.message);

                icon.textContent = '❌';
                text.textContent = 'Failed to generate';

                setTimeout(() => {
                    icon.textContent = '🎨';
                    text.textContent = originalText;
                    btn.disabled = false;
                }, 2000);
            }
        });
    </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
