<?php
/**
 * LightBlog CMS - Users Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Users';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;
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
                // Don't delete current user
                if ((int)$id !== $_SESSION['user_id']) {
                    $db->delete('users', 'id = ?', [(int)$id]);
                    $count++;
                }
            }
            $message = "✅ Deleted {$count} user(s)";
        }
        $action = 'list';
    } elseif (isset($_POST['delete'])) {
        if ((int)$_POST['user_id'] !== $_SESSION['user_id']) {
            $db->delete('users', 'id = ?', [$_POST['user_id']]);
            $message = 'User deleted successfully';
        } else {
            $message = 'Cannot delete your own account';
            $messageType = 'error';
        }
        $action = 'list';
    } elseif (isset($_POST['save'])) {
        $data = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'role' => $_POST['role'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Only update password if provided
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        if ($userId) {
            $db->update('users', $data, 'id = :id', ['id' => $userId]);
            $message = 'User updated successfully';
        } else {
            if (empty($_POST['password'])) {
                $message = 'Password is required for new users';
                $messageType = 'error';
            } else {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $data['created_at'] = date('Y-m-d H:i:s');
                $userId = $db->insert('users', $data);
                $message = 'User created successfully';
            }
        }
        if ($message && $messageType === 'success') {
            $action = 'list';
        }
    }
}

// Get user data for edit
$user = null;
if ($userId && $action === 'edit') {
    $user = $db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
}

// Get all users
$users = [];
if ($action === 'list') {
    $users = $db->query("
        SELECT u.*,
               (SELECT COUNT(*) FROM posts WHERE author_id = u.id) as post_count
        FROM users u
        ORDER BY u.created_at DESC
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
            <h2 class="card-title">All Users</h2>
            <a href="?action=new" class="btn btn-primary">+ New User</a>
        </div>

        <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <h3>No Users</h3>
                <p>Create user accounts</p>
                <a href="?action=new" class="btn btn-primary">Create User</a>
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
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Posts</th>
                                <th>Joined</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <?php if ($u->id !== $_SESSION['user_id']): ?>
                                            <input type="checkbox" name="selected_items[]" value="<?= $u->id ?>" class="item-checkbox" onchange="updateSelectedCount()">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($u->username) ?></strong>
                                        <?php if ($u->id === $_SESSION['user_id']): ?>
                                            <span class="badge badge-info">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u->email) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $u->role === 'admin' ? 'danger' : 'info' ?>">
                                            <?= $u->role ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($u->post_count) ?></td>
                                    <td><?= date('M j, Y', strtotime($u->created_at)) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <a href="?action=edit&id=<?= $u->id ?>" class="btn btn-sm btn-outline">✏️ Edit</a>
                                            <?php if ($u->id !== $_SESSION['user_id']): ?>
                                                <form method="POST" style="display:inline; margin: 0;" onsubmit="return confirm('Delete this user?');">
                                                    <input type="hidden" name="user_id" value="<?= $u->id ?>">
                                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">🗑️</button>
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
                        alert('Please select at least one user.');
                        return false;
                    }

                    return confirm(`Delete ${checked} user(s)? This cannot be undone.`);
                }

                document.addEventListener('DOMContentLoaded', updateSelectedCount);
            </script>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $action === 'new' ? 'Create User' : 'Edit User' ?></h2>
            <a href="?action=list" class="btn btn-outline">← Back</a>
        </div>

        <form method="POST">
            <?php if ($userId): ?>
                <input type="hidden" name="user_id" value="<?= $userId ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user->username ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->email ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password <?= $action === 'new' ? '*' : '(leave blank to keep current)' ?></label>
                <input type="password" id="password" name="password" <?= $action === 'new' ? 'required' : '' ?>>
                <small>Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="admin" <?= ($user->role ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="editor" <?= ($user->role ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="author" <?= ($user->role ?? 'author') === 'author' ? 'selected' : '' ?>>Author</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" name="save" class="btn btn-primary">
                    <?= $action === 'new' ? 'Create User' : 'Update User' ?>
                </button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
