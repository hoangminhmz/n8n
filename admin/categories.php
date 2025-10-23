<?php
/**
 * LightBlog CMS - Categories Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Categories';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$action = $_GET['action'] ?? 'list';
$categoryId = $_GET['id'] ?? null;
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
                $db->delete('categories', 'id = ?', [(int)$id]);
                $count++;
            }
            $message = "✅ Deleted {$count} category(s)";
        }
        $action = 'list';
    } elseif (isset($_POST['delete'])) {
        $db->delete('categories', 'id = ?', [$_POST['category_id']]);
        $message = 'Category deleted successfully';
        $action = 'list';
    } elseif (isset($_POST['save'])) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $_POST['name'])));

        // Check if slug exists
        $existing = $db->queryOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [
            $slug,
            $_POST['category_id'] ?? 0
        ]);

        if ($existing) {
            $slug .= '-' . time();
        }

        $data = [
            'name' => $_POST['name'],
            'slug' => $slug,
            'description' => $_POST['description'] ?? ''
        ];

        if ($categoryId) {
            $db->update('categories', $data, 'id = :id', ['id' => $categoryId]);
            $message = 'Category updated successfully';
        } else {
            $categoryId = $db->insert('categories', $data);
            $message = 'Category created successfully';
        }
        $action = 'list';
    }
}

// Get category data for edit
$category = null;
if ($categoryId && $action === 'edit') {
    $category = $db->queryOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
}

// Get all categories
$categories = [];
if ($action === 'list') {
    $categories = $db->query("
        SELECT c.*,
               (SELECT COUNT(*) FROM post_categories pc WHERE pc.category_id = c.id) as post_count
        FROM categories c
        ORDER BY c.name ASC
    ");
}

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
            <h2 class="card-title">All Categories</h2>
            <a href="?action=new" class="btn btn-primary">+ New Category</a>
        </div>

        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📁</div>
                <h3>No Categories Yet</h3>
                <p>Create your first category to organize posts</p>
                <a href="?action=new" class="btn btn-primary">Create Category</a>
            </div>
        <?php else: ?>
            <form method="POST" id="bulkForm">
                <div style="display: flex; gap: 1rem; align-items: center; padding: 1rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <select name="bulk_action" id="bulkAction" class="form-control" style="width: 200px;">
                        <option value="">Bulk Actions</option>
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
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Posts</th>
                                <th style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_items[]" value="<?= $cat->id ?>" class="item-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td><strong><?= htmlspecialchars($cat->name) ?></strong></td>
                                    <td><code><?= htmlspecialchars($cat->slug) ?></code></td>
                                    <td><?= htmlspecialchars($cat->description ?: '-') ?></td>
                                    <td><?= number_format($cat->post_count) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <a href="?action=edit&id=<?= $cat->id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                            <a href="<?= SITE_URL ?><?= BASE_PATH ?>category/<?= $cat->slug ?>" target="_blank" class="btn btn-sm btn-outline">👁️ View</a>
                                            <form method="POST" style="display:inline; margin: 0;" onsubmit="return confirm('Delete this category?');">
                                                <input type="hidden" name="category_id" value="<?= $cat->id ?>">
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
                        alert('Please select at least one category.');
                        return false;
                    }

                    return confirm(`Delete ${checked} category(s)?`);
                }

                document.addEventListener('DOMContentLoaded', updateSelectedCount);
            </script>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $action === 'new' ? 'Create Category' : 'Edit Category' ?></h2>
            <a href="?action=list" class="btn btn-outline">← Back</a>
        </div>

        <form method="POST">
            <?php if ($categoryId): ?>
                <input type="hidden" name="category_id" value="<?= $categoryId ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($category->name ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($category->description ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" name="save" class="btn btn-primary">
                    <?= $action === 'new' ? 'Create Category' : 'Update Category' ?>
                </button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
