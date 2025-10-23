<?php
/**
 * LightBlog CMS - Menu Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Menus';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$action = $_GET['action'] ?? 'edit';
$menuId = $_GET['menu_id'] ?? null;
$message = '';
$messageType = 'success';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_menu'])) {
        // Create new menu
        $menuId = $db->insert('menus', [
            'name' => $_POST['menu_name'],
            'location' => $_POST['menu_location'],
            'description' => $_POST['menu_description'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $message = '✅ Menu created successfully';
    } elseif (isset($_POST['update_menu'])) {
        // Update menu details
        $db->update('menus', [
            'name' => $_POST['menu_name'],
            'location' => $_POST['menu_location'],
            'description' => $_POST['menu_description'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $_POST['menu_id']]);
        $message = '✅ Menu updated successfully';
        $menuId = $_POST['menu_id'];
    } elseif (isset($_POST['delete_menu'])) {
        // Delete menu and its items
        $db->delete('menu_items', 'menu_id = ?', [$_POST['menu_id']]);
        $db->delete('menus', 'id = ?', [$_POST['menu_id']]);
        $message = '✅ Menu deleted successfully';
        $menuId = null;
    } elseif (isset($_POST['save_menu_items'])) {
        // Save menu items
        $menuId = $_POST['menu_id'];
        $items = json_decode($_POST['menu_items_data'], true);

        // Delete existing items
        $db->delete('menu_items', 'menu_id = ?', [$menuId]);

        // Insert new items
        foreach ($items as $item) {
            $db->insert('menu_items', [
                'menu_id' => $menuId,
                'type' => $item['type'],
                'object_id' => $item['object_id'] ?: null,
                'custom_url' => $item['custom_url'] ?: null,
                'title' => $item['title'],
                'css_classes' => $item['css_classes'] ?? '',
                'target' => $item['target'] ?? '_self',
                'parent_id' => $item['parent_id'] ?? 0,
                'menu_order' => $item['menu_order'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $message = '✅ Menu saved successfully';
    } elseif (isset($_POST['delete_menu_item'])) {
        // Delete single menu item
        $db->delete('menu_items', 'id = ?', [$_POST['item_id']]);
        $message = '✅ Menu item deleted';
        $menuId = $_POST['menu_id'];
    }
}

// Get all menus
$menus = $db->query("SELECT * FROM menus ORDER BY created_at DESC");

// Get current menu
$currentMenu = null;
if ($menuId) {
    $currentMenu = $db->queryOne("SELECT * FROM menus WHERE id = ?", [$menuId]);
} elseif (!empty($menus)) {
    $currentMenu = $menus[0];
    $menuId = $currentMenu->id;
}

// Get menu items for current menu
$menuItems = [];
if ($menuId) {
    $menuItems = $db->query("
        SELECT * FROM menu_items
        WHERE menu_id = ?
        ORDER BY parent_id, menu_order
    ", [$menuId]);
}

// Get available items for adding to menu
$availablePages = $db->query("SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title");
$availableCategories = $db->query("SELECT id, name, slug FROM categories ORDER BY name");

include __DIR__ . '/includes/header.php';
?>

<style>
.menu-builder {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    margin-top: 1rem;
}

.menu-selector {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    height: fit-content;
}

.menu-editor {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.menu-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu-list-item {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.menu-list-item:hover {
    background: #f3f4f6;
}

.menu-list-item.active {
    background: #dbeafe;
    border-left: 3px solid #3b82f6;
}

.available-items {
    margin-top: 1.5rem;
}

.item-group {
    margin-bottom: 1.5rem;
}

.item-group h4 {
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    color: #6b7280;
}

.item-list {
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.item-checkbox {
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.item-checkbox:last-child {
    border-bottom: none;
}

.menu-structure {
    min-height: 300px;
    padding: 1rem;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.menu-structure.empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.menu-item-block {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.menu-item-block.child {
    margin-left: 2rem;
    background: #f9fafb;
}

.menu-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menu-item-title {
    font-weight: 600;
    color: #111827;
}

.menu-item-type {
    font-size: 0.75rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.menu-item-actions {
    display: flex;
    gap: 0.5rem;
}

.custom-link-form {
    display: none;
    margin-top: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 4px;
}

.custom-link-form.active {
    display: block;
}
</style>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<div class="menu-builder">
    <!-- Left Sidebar: Menu Selector -->
    <div class="menu-selector">
        <h3>Select Menu</h3>

        <ul class="menu-list">
            <?php foreach ($menus as $menu): ?>
                <li class="menu-list-item <?= $menu->id == $menuId ? 'active' : '' ?>">
                    <a href="?action=edit&menu_id=<?= $menu->id ?>" style="text-decoration: none; color: inherit; display: block;">
                        <strong><?= htmlspecialchars($menu->name) ?></strong>
                        <?php if ($menu->location): ?>
                            <br><small style="color: #6b7280;"><?= htmlspecialchars($menu->location) ?></small>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <button type="button" class="btn btn-outline" onclick="toggleCreateMenu()" style="width: 100%; margin-top: 1rem;">
            + Create New Menu
        </button>

        <div id="createMenuForm" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            <form method="POST">
                <div class="form-group">
                    <label>Menu Name</label>
                    <input type="text" name="menu_name" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <select name="menu_location">
                        <option value="primary">Primary (Header)</option>
                        <option value="footer">Footer</option>
                        <option value="mobile">Mobile</option>
                        <option value="sidebar">Sidebar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="menu_description" rows="2"></textarea>
                </div>
                <button type="submit" name="create_menu" class="btn btn-primary">Create</button>
            </form>
        </div>

        <!-- Available Items to Add -->
        <div class="available-items">
            <h3 style="margin-bottom: 1rem;">Add Items</h3>

            <!-- Pages -->
            <div class="item-group">
                <h4>📄 Pages</h4>
                <div class="item-list">
                    <?php foreach ($availablePages as $page): ?>
                        <div class="item-checkbox">
                            <input type="checkbox" class="add-item-checkbox" data-type="page" data-id="<?= $page->id ?>" data-title="<?= htmlspecialchars($page->title) ?>">
                            <label><?= htmlspecialchars($page->title) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addSelectedItems('page')" style="margin-top: 0.5rem; width: 100%;">Add to Menu</button>
            </div>

            <!-- Categories -->
            <div class="item-group">
                <h4>📁 Categories</h4>
                <div class="item-list">
                    <?php foreach ($availableCategories as $cat): ?>
                        <div class="item-checkbox">
                            <input type="checkbox" class="add-item-checkbox" data-type="category" data-id="<?= $cat->id ?>" data-title="<?= htmlspecialchars($cat->name) ?>">
                            <label><?= htmlspecialchars($cat->name) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addSelectedItems('category')" style="margin-top: 0.5rem; width: 100%;">Add to Menu</button>
            </div>

            <!-- Custom Link -->
            <div class="item-group">
                <h4>🔗 Custom Link</h4>
                <button type="button" class="btn btn-sm btn-outline" onclick="toggleCustomLinkForm()" style="width: 100%;">+ Add Custom Link</button>
                <div id="customLinkForm" class="custom-link-form">
                    <div class="form-group">
                        <label>URL</label>
                        <input type="url" id="custom_url" placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label>Link Text</label>
                        <input type="text" id="custom_title" placeholder="My Link">
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addCustomLink()">Add to Menu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Menu Editor -->
    <div class="menu-editor">
        <?php if ($currentMenu): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2><?= htmlspecialchars($currentMenu->name) ?></h2>
                    <p style="color: #6b7280; margin-top: 0.25rem;"><?= htmlspecialchars($currentMenu->description) ?></p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline" onclick="editMenuDetails()">✏️ Edit Details</button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this menu?');">
                        <input type="hidden" name="menu_id" value="<?= $currentMenu->id ?>">
                        <button type="submit" name="delete_menu" class="btn btn-danger">🗑️ Delete</button>
                    </form>
                </div>
            </div>

            <form method="POST" id="menuForm">
                <input type="hidden" name="menu_id" value="<?= $currentMenu->id ?>">
                <input type="hidden" name="menu_items_data" id="menuItemsData">

                <div id="menuStructure" class="menu-structure <?= empty($menuItems) ? 'empty' : '' ?>">
                    <?php if (empty($menuItems)): ?>
                        <p>No menu items yet. Add items from the left sidebar.</p>
                    <?php else: ?>
                        <?php foreach ($menuItems as $item): ?>
                            <div class="menu-item-block <?= $item->parent_id > 0 ? 'child' : '' ?>" data-item-id="<?= $item->id ?>">
                                <div class="menu-item-header">
                                    <div>
                                        <span class="menu-item-title"><?= htmlspecialchars($item->title) ?></span>
                                        <span class="menu-item-type"><?= $item->type ?></span>
                                    </div>
                                    <div class="menu-item-actions">
                                        <button type="button" class="btn btn-sm btn-outline" onclick="editMenuItem(<?= $item->id ?>)">✏️</button>
                                        <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Remove this item?');">
                                            <input type="hidden" name="menu_id" value="<?= $currentMenu->id ?>">
                                            <input type="hidden" name="item_id" value="<?= $item->id ?>">
                                            <button type="submit" name="delete_menu_item" class="btn btn-sm btn-danger">×</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="submit" name="save_menu_items" class="btn btn-primary">💾 Save Menu</button>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <p style="color: #9ca3af; font-size: 1.125rem;">No menu selected</p>
                <p style="color: #6b7280; margin-top: 0.5rem;">Create a new menu or select one from the left</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let menuItemsArray = <?= json_encode($menuItems ?: []) ?>;

function toggleCreateMenu() {
    const form = document.getElementById('createMenuForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleCustomLinkForm() {
    const form = document.getElementById('customLinkForm');
    form.classList.toggle('active');
}

function addSelectedItems(type) {
    const checkboxes = document.querySelectorAll(`.add-item-checkbox[data-type="${type}"]:checked`);
    const structure = document.getElementById('menuStructure');

    if (structure.classList.contains('empty')) {
        structure.innerHTML = '';
        structure.classList.remove('empty');
    }

    checkboxes.forEach(checkbox => {
        const id = checkbox.dataset.id;
        const title = checkbox.dataset.title;

        const itemBlock = document.createElement('div');
        itemBlock.className = 'menu-item-block';
        itemBlock.innerHTML = `
            <div class="menu-item-header">
                <div>
                    <span class="menu-item-title">${title}</span>
                    <span class="menu-item-type">${type}</span>
                </div>
                <div class="menu-item-actions">
                    <button type="button" class="btn btn-sm btn-outline" onclick="editMenuItem(this)">✏️</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeMenuItem(this)">×</button>
                </div>
            </div>
        `;
        itemBlock.dataset.type = type;
        itemBlock.dataset.objectId = id;
        itemBlock.dataset.title = title;

        structure.appendChild(itemBlock);
        checkbox.checked = false;
    });
}

function addCustomLink() {
    const url = document.getElementById('custom_url').value;
    const title = document.getElementById('custom_title').value;

    if (!url || !title) {
        alert('Please enter both URL and title');
        return;
    }

    const structure = document.getElementById('menuStructure');
    if (structure.classList.contains('empty')) {
        structure.innerHTML = '';
        structure.classList.remove('empty');
    }

    const itemBlock = document.createElement('div');
    itemBlock.className = 'menu-item-block';
    itemBlock.innerHTML = `
        <div class="menu-item-header">
            <div>
                <span class="menu-item-title">${title}</span>
                <span class="menu-item-type">custom</span>
            </div>
            <div class="menu-item-actions">
                <button type="button" class="btn btn-sm btn-outline" onclick="editMenuItem(this)">✏️</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeMenuItem(this)">×</button>
            </div>
        </div>
    `;
    itemBlock.dataset.type = 'custom';
    itemBlock.dataset.customUrl = url;
    itemBlock.dataset.title = title;

    structure.appendChild(itemBlock);

    document.getElementById('custom_url').value = '';
    document.getElementById('custom_title').value = '';
    toggleCustomLinkForm();
}

function removeMenuItem(button) {
    if (confirm('Remove this menu item?')) {
        button.closest('.menu-item-block').remove();

        const structure = document.getElementById('menuStructure');
        if (structure.children.length === 0) {
            structure.innerHTML = '<p>No menu items yet. Add items from the left sidebar.</p>';
            structure.classList.add('empty');
        }
    }
}

// Serialize menu items before form submit
document.getElementById('menuForm')?.addEventListener('submit', function(e) {
    const items = [];
    const blocks = document.querySelectorAll('.menu-item-block');

    blocks.forEach((block, index) => {
        items.push({
            type: block.dataset.type,
            object_id: block.dataset.objectId || null,
            custom_url: block.dataset.customUrl || null,
            title: block.dataset.title,
            css_classes: block.dataset.cssClasses || '',
            target: block.dataset.target || '_self',
            parent_id: 0,
            menu_order: index
        });
    });

    document.getElementById('menuItemsData').value = JSON.stringify(items);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
