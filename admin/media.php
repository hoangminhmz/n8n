<?php
/**
 * LightBlog CMS - Media Library Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';

$pageTitle = 'Media Library';
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

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
                $media = $db->queryOne("SELECT * FROM media WHERE id = ?", [(int)$id]);
                if ($media) {
                    // Delete physical file
                    $filePath = SITE_PATH . '/content/uploads/' . $media->filename;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $db->delete('media', 'id = ?', [(int)$id]);
                    $count++;
                }
            }
            $message = "✅ Deleted {$count} media file(s)";
        }
    } elseif (isset($_POST['delete_item'])) {
        $media = $db->queryOne("SELECT * FROM media WHERE id = ?", [$_POST['media_id']]);
        if ($media) {
            $filePath = SITE_PATH . '/content/uploads/' . $media->filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $db->delete('media', 'id = ?', [$_POST['media_id']]);
            $message = 'Media deleted successfully';
        }
    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $uploadDir = SITE_PATH . '/content/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $originalName = basename($_FILES['file']['name']);
        $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileName = time() . '-' . preg_replace('/[^a-z0-9]/', '-', strtolower(pathinfo($originalName, PATHINFO_FILENAME))) . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;

        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'zip'];
        if (!in_array($fileExt, $allowedTypes)) {
            $message = 'File type not allowed. Allowed: ' . implode(', ', $allowedTypes);
            $messageType = 'error';
        } else {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                // Save to database
                $db->insert('media', [
                    'filename' => $fileName,
                    'original_name' => $originalName,
                    'file_path' => '/content/uploads/' . $fileName,
                    'file_size' => $_FILES['file']['size'],
                    'mime_type' => $_FILES['file']['type'],
                    'uploaded_by' => $_SESSION['user_id'],
                    'uploaded_at' => date('Y-m-d H:i:s')
                ]);
                $message = 'File uploaded successfully';
            } else {
                $message = 'Failed to upload file';
                $messageType = 'error';
            }
        }
    }
}

// Get all media files
$mediaFiles = $db->query("
    SELECT m.*, u.username
    FROM media m
    LEFT JOIN users u ON m.uploaded_by = u.id
    ORDER BY m.uploaded_at DESC
");

include __DIR__ . '/includes/header.php';
?>

<style>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.media-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    transition: all 0.2s;
}

.media-item:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.media-item.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.media-preview {
    width: 100%;
    height: 150px;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-preview .file-icon {
    font-size: 3rem;
}

.media-checkbox {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    width: 20px;
    height: 20px;
}

.media-info {
    padding: 0.75rem;
}

.media-actions {
    display: flex;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.upload-box {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    background: #f9fafb;
    margin-bottom: 2rem;
}
</style>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Media Library</h2>
    </div>

    <!-- Upload Form -->
    <div class="upload-box">
        <form method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">📁</div>
                <h3>Upload Media</h3>
                <p style="color: #6b7280;">Drag and drop or click to upload</p>
            </div>
            <input type="file" name="file" accept="image/*,.pdf,.zip" required onchange="this.form.submit()" style="max-width: 300px;">
            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                Allowed: JPG, PNG, GIF, WebP, SVG, PDF, ZIP
            </div>
        </form>
    </div>

    <?php if (empty($mediaFiles)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🖼️</div>
            <h3>No Media Files</h3>
            <p>Upload images and files to your media library</p>
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

            <div class="media-grid">
                <?php foreach ($mediaFiles as $m): ?>
                    <div class="media-item" id="media-<?= $m->id ?>">
                        <div class="media-preview">
                            <input type="checkbox" name="selected_items[]" value="<?= $m->id ?>" class="media-checkbox item-checkbox" onchange="updateSelected(<?= $m->id ?>)">
                            <?php if (preg_match('/image/', $m->mime_type)): ?>
                                <img src="<?= BASE_PATH . $m->file_path ?>" alt="<?= htmlspecialchars($m->original_name) ?>">
                            <?php else: ?>
                                <div class="file-icon">📄</div>
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($m->original_name) ?>">
                                <?= htmlspecialchars($m->original_name) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #6b7280;">
                                <?= number_format($m->file_size / 1024, 1) ?> KB
                            </div>
                        </div>
                        <div class="media-actions">
                            <button type="button" class="btn btn-sm btn-outline" onclick="copyUrl('<?= SITE_URL . BASE_PATH . $m->file_path ?>')">📋 Copy URL</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteMedia(<?= $m->id ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>

        <script>
            function updateSelected(id) {
                const item = document.getElementById('media-' + id);
                const checkbox = item.querySelector('.media-checkbox');
                if (checkbox.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
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
            }

            function confirmBulkAction() {
                const action = document.getElementById('bulkAction').value;
                const checked = document.querySelectorAll('.item-checkbox:checked').length;

                if (!action) {
                    alert('Please select an action.');
                    return false;
                }
                if (checked === 0) {
                    alert('Please select at least one file.');
                    return false;
                }

                return confirm(`Delete ${checked} file(s)? This will permanently delete the files.`);
            }

            function copyUrl(url) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('URL copied to clipboard: ' + url);
                });
            }

            function deleteMedia(id) {
                if (confirm('Delete this media file? This cannot be undone.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="media_id" value="${id}"><input type="hidden" name="delete_item" value="1">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
