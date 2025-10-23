<?php
/**
 * LightBlog CMS - Affiliate Products Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Affiliate Products';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;
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
                $db->delete('affiliate_products', 'id = ?', [(int)$id]);
                $count++;
            }
            $message = "✅ Deleted {$count} product(s)";
        }
        $action = 'list';
    } elseif (isset($_POST['delete'])) {
        $db->delete('affiliate_products', 'id = ?', [$_POST['product_id']]);
        $message = 'Product deleted successfully';
        $action = 'list';
    } elseif (isset($_POST['save'])) {
        $data = [
            'product_name' => $_POST['product_name'],
            'product_url' => $_POST['product_url'],
            'affiliate_url' => $_POST['affiliate_url'],
            'image_url' => $_POST['image_url'] ?? null,
            'price' => !empty($_POST['price']) ? (float)$_POST['price'] : null,
            'category' => $_POST['category'] ?? '',
            'keywords' => json_encode(array_filter(array_map('trim', explode(',', $_POST['keywords'] ?? '')))),
            'last_updated' => date('Y-m-d H:i:s')
        ];

        if ($productId) {
            $db->update('affiliate_products', $data, 'id = :id', ['id' => $productId]);
            $message = 'Product updated successfully';
        } else {
            $productId = $db->insert('affiliate_products', $data);
            $message = 'Product created successfully';
        }
        $action = 'list';
    }
}

// Get product data for edit
$product = null;
if ($productId && $action === 'edit') {
    $product = $db->queryOne("SELECT * FROM affiliate_products WHERE id = ?", [$productId]);
}

// Get all products
$products = [];
if ($action === 'list') {
    $products = $db->query("
        SELECT *
        FROM affiliate_products
        ORDER BY product_name ASC
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
            <h2 class="card-title">Affiliate Products</h2>
            <a href="?action=new" class="btn btn-primary">+ New Product</a>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">💰</div>
                <h3>No Products Yet</h3>
                <p>Add affiliate products to monetize your content</p>
                <a href="?action=new" class="btn btn-primary">Add Product</a>
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
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Clicks</th>
                                <th>Revenue</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_items[]" value="<?= $p->id ?>" class="item-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                                            <?php if ($p->image_url): ?>
                                                <img src="<?= htmlspecialchars($p->image_url) ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($p->product_name) ?></strong>
                                                <br><small style="color: #6b7280;"><?= htmlspecialchars($p->product_url) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($p->category ?: '-') ?></td>
                                    <td><?= $p->price ? '$' . number_format($p->price, 2) : '-' ?></td>
                                    <td><?= number_format($p->clicks) ?></td>
                                    <td><?= $p->revenue ? '$' . number_format($p->revenue, 2) : '$0.00' ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <a href="?action=edit&id=<?= $p->id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                            <form method="POST" style="display:inline; margin: 0;" onsubmit="return confirm('Delete this product?');">
                                                <input type="hidden" name="product_id" value="<?= $p->id ?>">
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
                        alert('Please select at least one product.');
                        return false;
                    }

                    return confirm(`Delete ${checked} product(s)?`);
                }

                document.addEventListener('DOMContentLoaded', updateSelectedCount);
            </script>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $action === 'new' ? 'Add Product' : 'Edit Product' ?></h2>
            <a href="?action=list" class="btn btn-outline">← Back</a>
        </div>

        <form method="POST">
            <?php if ($productId): ?>
                <input type="hidden" name="product_id" value="<?= $productId ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="product_name">Product Name *</label>
                <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($product->product_name ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="product_url">Product URL *</label>
                    <input type="url" id="product_url" name="product_url" value="<?= htmlspecialchars($product->product_url ?? '') ?>" required>
                    <small>The official product page</small>
                </div>

                <div class="form-group">
                    <label for="affiliate_url">Affiliate URL *</label>
                    <input type="url" id="affiliate_url" name="affiliate_url" value="<?= htmlspecialchars($product->affiliate_url ?? '') ?>" required>
                    <small>Your affiliate tracking link</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url" value="<?= htmlspecialchars($product->image_url ?? '') ?>">
                    <small>Product image for display</small>
                </div>

                <div class="form-group">
                    <label for="price">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?= $product->price ?? '' ?>">
                    <small>Optional product price</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($product->category ?? '') ?>">
                    <small>e.g., Electronics, Software, Books</small>
                </div>

                <div class="form-group">
                    <label for="keywords">Keywords</label>
                    <input type="text" id="keywords" name="keywords" value="<?= htmlspecialchars(implode(', ', json_decode($product->keywords ?? '[]', true))) ?>">
                    <small>Comma-separated for content matching</small>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" name="save" class="btn btn-primary">
                    <?= $action === 'new' ? 'Add Product' : 'Update Product' ?>
                </button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
