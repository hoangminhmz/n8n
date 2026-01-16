<?php
/**
 * LightBlog CMS - Pages Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Pages';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$action = $_GET['action'] ?? 'list';
$pageItemId = $_GET['id'] ?? null;
$message = '';
$messageType = 'success';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action']) && !empty($_POST['selected_items'])) {
        $bulkAction = $_POST['bulk_action'];
        $items = $_POST['selected_items'];
        $count = 0;

        if ($bulkAction === 'delete') {
            foreach ($items as $id) {
                $db->delete('pages', 'id = ?', [(int)$id]);
                $count++;
            }
            $message = "✅ Deleted {$count} page(s)";
        } elseif ($bulkAction === 'publish') {
            foreach ($items as $id) {
                $db->update('pages', [
                    'status' => 'published',
                    'published_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => (int)$id]);
                $count++;
            }
            $message = "✅ Published {$count} page(s)";
        } elseif ($bulkAction === 'draft') {
            foreach ($items as $id) {
                $db->update('pages', ['status' => 'draft'], 'id = :id', ['id' => (int)$id]);
                $count++;
            }
            $message = "✅ Changed {$count} page(s) to draft";
        }
        $action = 'list';
    } elseif (isset($_POST['delete'])) {
        $db->delete('pages', 'id = ?', [$_POST['page_id']]);
        $message = 'Page deleted successfully';
        $action = 'list';
    } elseif (isset($_POST['save'])) {
        // Generate slug
        $slug = strtolower(trim($_POST['slug'] ?: $_POST['title']));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check if slug exists
        $existing = $db->queryOne("SELECT id FROM pages WHERE slug = ? AND id != ?", [
            $slug,
            $_POST['page_id'] ?? 0
        ]);

        if ($existing) {
            $slug .= '-' . time();
        }

        $data = [
            'title' => $_POST['title'],
            'slug' => $slug,
            'content' => $_POST['content'],
            'excerpt' => $_POST['excerpt'] ?? '',
            'parent_id' => (int)($_POST['parent_id'] ?? 0),
            'menu_order' => (int)($_POST['menu_order'] ?? 0),
            'template' => $_POST['template'] ?? 'default',
            'status' => $_POST['status'],
            'visibility' => $_POST['visibility'] ?? 'public',
            'custom_css' => $_POST['custom_css'] ?? '',
            'updated_at' => date('Y-m-d H:i:s'),
            // SEO
            'seo_title' => $_POST['seo_title'] ?? $_POST['title'],
            'meta_description' => $_POST['meta_description'] ?? '',
            'canonical_url' => $_POST['canonical_url'] ?? '',
            'meta_robots' => $_POST['meta_robots'] ?? 'index,follow',
            'focus_keyword' => $_POST['focus_keyword'] ?? '',
            // OG
            'og_title' => $_POST['og_title'] ?? '',
            'og_description' => $_POST['og_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            // Twitter
            'twitter_title' => $_POST['twitter_title'] ?? '',
            'twitter_description' => $_POST['twitter_description'] ?? '',
            'twitter_image' => $_POST['twitter_image'] ?? ''
        ];

        if ($pageItemId) {
            // Update
            $db->update('pages', $data, 'id = :id', ['id' => $pageItemId]);
            $message = 'Page updated successfully';
        } else {
            // Create
            $data['author_id'] = $auth->getCurrentUserId();
            $data['created_at'] = date('Y-m-d H:i:s');
            if ($data['status'] === 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            $pageItemId = $db->insert('pages', $data);
            $message = 'Page created successfully';
        }
        $action = 'list';
    }
}

// Get page data for edit
$page = null;
if ($pageItemId && $action === 'edit') {
    $page = $db->queryOne("SELECT * FROM pages WHERE id = ?", [$pageItemId]);
}

// Get all pages for list
$pages = [];
if ($action === 'list') {
    $pages = $db->query("SELECT * FROM pages ORDER BY menu_order ASC, title ASC");
}

// Get all pages for parent dropdown (excluding current page)
$allPages = $db->query("SELECT id, title, parent_id FROM pages WHERE id != ? ORDER BY title ASC", [$pageItemId ?? 0]);

include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Pages</h2>
            <a href="?action=new" class="btn btn-primary">+ New Page</a>
        </div>

        <?php if (empty($pages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📄</div>
                <h3>No Pages Yet</h3>
                <p>Create your first page (About, Contact, Privacy, etc.)</p>
                <a href="?action=new" class="btn btn-primary">Create Page</a>
            </div>
        <?php else: ?>
            <form method="POST" id="bulkForm">
                <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <select name="bulk_action" id="bulkAction" class="form-control" style="width: 200px;">
                        <option value="">Bulk Actions</option>
                        <option value="publish">Publish</option>
                        <option value="draft">Move to Draft</option>
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
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Template</th>
                                <th>Order</th>
                                <th>Views</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_items[]" value="<?= $p->id ?>" class="item-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p->title) ?></strong>
                                        <?php if ($p->parent_id > 0): ?>
                                            <br><small style="color: #6b7280;">└─ Child page</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($p->slug) ?></code></td>
                                    <td>
                                        <?php if ($p->status === 'published'): ?>
                                            <span class="badge badge-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($p->template) ?></td>
                                    <td><?= $p->menu_order ?></td>
                                    <td><?= number_format($p->views) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <a href="?action=edit&id=<?= $p->id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                            <a href="<?= SITE_URL ?><?= BASE_PATH ?><?= $p->slug ?>" target="_blank" class="btn btn-sm btn-outline">👁️ View</a>
                                            <form method="POST" style="display:inline; margin: 0;" onsubmit="return confirm('Delete this page?');">
                                                <input type="hidden" name="page_id" value="<?= $p->id ?>">
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
                    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checkbox.checked);
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

                    const selectAll = document.getElementById('selectAll');
                    selectAll.checked = checked === total && total > 0;
                    selectAll.indeterminate = checked > 0 && checked < total;
                }

                function confirmBulkAction() {
                    const action = document.getElementById('bulkAction').value;
                    const checked = document.querySelectorAll('.item-checkbox:checked').length;

                    if (!action) {
                        alert('Please select an action.');
                        return false;
                    }
                    if (checked === 0) {
                        alert('Please select at least one page.');
                        return false;
                    }

                    if (action === 'delete') {
                        return confirm(`Delete ${checked} page(s)?`);
                    }
                    return true;
                }

                document.addEventListener('DOMContentLoaded', updateSelectedCount);
            </script>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $action === 'new' ? 'Create Page' : 'Edit Page' ?></h2>
            <a href="?action=list" class="btn btn-outline">← Back</a>
        </div>

        <form method="POST">
            <?php if ($pageItemId): ?>
                <input type="hidden" name="page_id" value="<?= $pageItemId ?>">
            <?php endif; ?>

            <div class="form-tabs">
                <button type="button" class="tab-btn active" onclick="switchTab('content')">Content</button>
                <button type="button" class="tab-btn" onclick="switchTab('settings')">Settings</button>
                <button type="button" class="tab-btn" onclick="switchTab('seo')">SEO</button>
            </div>

            <!-- Content Tab -->
            <div id="tab-content" class="tab-content active">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($page->title ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug (URL)</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($page->slug ?? '') ?>" placeholder="Leave empty to auto-generate">
                    <small>Page will be accessible at: <?= SITE_URL ?><?= BASE_PATH ?><span id="slug-preview"><?= htmlspecialchars($page->slug ?? 'your-page-slug') ?></span></small>
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="20"><?= htmlspecialchars($page->content ?? '') ?></textarea>
                    <small>You can use HTML tags</small>
                </div>

                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($page->excerpt ?? '') ?></textarea>
                    <small>Short description (optional)</small>
                </div>

                <div class="form-group">
                    <label for="custom_css">Custom CSS</label>
                    <textarea id="custom_css" name="custom_css" rows="5"><?= htmlspecialchars($page->custom_css ?? '') ?></textarea>
                    <small>Add custom CSS for this page only</small>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="tab-settings" class="tab-content">
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="draft" <?= ($page->status ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($page->status ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="visibility">Visibility</label>
                        <select id="visibility" name="visibility">
                            <option value="public" <?= ($page->visibility ?? 'public') === 'public' ? 'selected' : '' ?>>Public</option>
                            <option value="private" <?= ($page->visibility ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="parent_id">Parent Page</label>
                        <select id="parent_id" name="parent_id">
                            <option value="0">None (Top Level)</option>
                            <?php foreach ($allPages as $p): ?>
                                <option value="<?= $p->id ?>" <?= ($page->parent_id ?? 0) == $p->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p->title) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Create hierarchical pages (Parent > Child)</small>
                    </div>

                    <div class="form-group">
                        <label for="menu_order">Menu Order</label>
                        <input type="number" id="menu_order" name="menu_order" value="<?= $page->menu_order ?? 0 ?>" min="0">
                        <small>Lower numbers appear first</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="template">Template</label>
                    <select id="template" name="template">
                        <option value="default" <?= ($page->template ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                        <option value="full-width" <?= ($page->template ?? '') === 'full-width' ? 'selected' : '' ?>>Full Width</option>
                        <option value="contact" <?= ($page->template ?? '') === 'contact' ? 'selected' : '' ?>>Contact</option>
                        <option value="landing" <?= ($page->template ?? '') === 'landing' ? 'selected' : '' ?>>Landing Page</option>
                    </select>
                    <small>Choose page template (themes/default/page-{template}.php)</small>
                </div>
            </div>

            <!-- SEO Tab -->
            <div id="tab-seo" class="tab-content">
                <div class="form-group">
                    <label for="seo_title">SEO Title</label>
                    <input type="text" id="seo_title" name="seo_title" value="<?= htmlspecialchars($page->seo_title ?? '') ?>" maxlength="60">
                    <small>Optimal: 50-60 characters</small>
                </div>

                <div class="form-group">
                    <label for="meta_description">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="3" maxlength="160"><?= htmlspecialchars($page->meta_description ?? '') ?></textarea>
                    <small>Optimal: 150-160 characters</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="focus_keyword">Focus Keyword</label>
                        <input type="text" id="focus_keyword" name="focus_keyword" value="<?= htmlspecialchars($page->focus_keyword ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="meta_robots">Meta Robots</label>
                        <select id="meta_robots" name="meta_robots">
                            <option value="index,follow" <?= ($page->meta_robots ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>Index, Follow</option>
                            <option value="noindex,follow" <?= ($page->meta_robots ?? '') === 'noindex,follow' ? 'selected' : '' ?>>No Index, Follow</option>
                            <option value="index,nofollow" <?= ($page->meta_robots ?? '') === 'index,nofollow' ? 'selected' : '' ?>>Index, No Follow</option>
                            <option value="noindex,nofollow" <?= ($page->meta_robots ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>No Index, No Follow</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="canonical_url">Canonical URL</label>
                    <input type="url" id="canonical_url" name="canonical_url" value="<?= htmlspecialchars($page->canonical_url ?? '') ?>">
                    <small>Leave empty to auto-generate</small>
                </div>

                <h3 style="margin-top: 2rem;">Open Graph (Facebook)</h3>
                <div class="form-group">
                    <label for="og_title">OG Title</label>
                    <input type="text" id="og_title" name="og_title" value="<?= htmlspecialchars($page->og_title ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="og_description">OG Description</label>
                    <textarea id="og_description" name="og_description" rows="2"><?= htmlspecialchars($page->og_description ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="og_image">OG Image URL</label>
                    <input type="url" id="og_image" name="og_image" value="<?= htmlspecialchars($page->og_image ?? '') ?>">
                </div>

                <h3 style="margin-top: 2rem;">Twitter Cards</h3>
                <div class="form-group">
                    <label for="twitter_title">Twitter Title</label>
                    <input type="text" id="twitter_title" name="twitter_title" value="<?= htmlspecialchars($page->twitter_title ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="twitter_description">Twitter Description</label>
                    <textarea id="twitter_description" name="twitter_description" rows="2"><?= htmlspecialchars($page->twitter_description ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="twitter_image">Twitter Image URL</label>
                    <input type="url" id="twitter_image" name="twitter_image" value="<?= htmlspecialchars($page->twitter_image ?? '') ?>">
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <button type="submit" name="save" class="btn btn-primary">
                    <?= $action === 'new' ? 'Create Page' : 'Update Page' ?>
                </button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <style>
        .form-tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: -2px;
        }
        .tab-btn.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        #slug-preview {
            color: #3b82f6;
            font-weight: 500;
        }
    </style>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Auto-update slug preview
        document.getElementById('slug').addEventListener('input', function() {
            const slugPreview = document.getElementById('slug-preview');
            slugPreview.textContent = this.value || 'your-page-slug';
        });

        // Auto-generate slug from title
        document.getElementById('title').addEventListener('blur', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value) {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
                document.getElementById('slug-preview').textContent = slug;
            }
        });
    </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
