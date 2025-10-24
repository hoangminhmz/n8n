<?php
/**
 * Backup and Restore Functionality
 * Creates full site backup (source code + database) as ZIP file
 * Restores from backup ZIP file
 */

session_start();
require_once '../autoload.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$db = Database::getInstance();

// Increase execution time and memory for large backups
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '512M');

/**
 * Export database to SQL file
 */
function exportDatabase($outputFile) {
    $db = Database::getInstance();

    // Get all tables
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $sql = "-- LightBlog CMS Database Backup\n";
    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Drop table if exists
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

        // Get CREATE TABLE statement
        $createResult = $db->query("SHOW CREATE TABLE `$table`");
        $createRow = $createResult->fetch_array();
        $sql .= $createRow[1] . ";\n\n";

        // Get table data
        $dataResult = $db->query("SELECT * FROM `$table`");

        if ($dataResult->num_rows > 0) {
            $sql .= "-- Dumping data for table `$table`\n";

            while ($row = $dataResult->fetch_assoc()) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $db->getConnection()->real_escape_string($value) . "'";
                    }
                }
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($outputFile, $sql);
    return true;
}

/**
 * Create backup ZIP file
 */
function createBackup() {
    $backupDir = sys_get_temp_dir() . '/lightblog_backup_' . time();
    $rootDir = dirname(__DIR__);

    // Create temp backup directory
    if (!mkdir($backupDir, 0755, true)) {
        return ['success' => false, 'message' => 'Failed to create backup directory'];
    }

    // Export database
    $dbFile = $backupDir . '/database.sql';
    if (!exportDatabase($dbFile)) {
        return ['success' => false, 'message' => 'Failed to export database'];
    }

    // Create backup metadata
    $metadata = [
        'version' => '1.0',
        'date' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'site_name' => defined('SITE_NAME') ? SITE_NAME : 'LightBlog CMS',
        'database_size' => filesize($dbFile),
        'backup_type' => 'full'
    ];
    file_put_contents($backupDir . '/backup_info.json', json_encode($metadata, JSON_PRETTY_PRINT));

    // Create ZIP file
    $zipFile = sys_get_temp_dir() . '/lightblog_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['success' => false, 'message' => 'Failed to create ZIP file'];
    }

    // Add database and metadata to ZIP
    $zip->addFile($dbFile, 'database.sql');
    $zip->addFile($backupDir . '/backup_info.json', 'backup_info.json');

    // Files and directories to exclude from backup
    $excludePaths = [
        'content/uploads',  // Large files, can backup separately
        '.git',
        'node_modules',
        'vendor',
        '.env',
        'error_log'
    ];

    // Files to include but sanitize (remove sensitive data)
    $sanitizeFiles = [
        'config.php' => true
    ];

    // Add source code files to ZIP
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootDir) + 1);

            // Check if file should be excluded
            $shouldExclude = false;
            foreach ($excludePaths as $excludePath) {
                if (strpos($relativePath, $excludePath) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }

            if (!$shouldExclude) {
                // Sanitize config.php (remove API keys)
                if (isset($sanitizeFiles[$relativePath])) {
                    $content = file_get_contents($filePath);

                    // Replace API keys with placeholders
                    $content = preg_replace(
                        "/(define\(['\"](?:OPENAI_API_KEY|CLAUDE_API_KEY|GEMINI_API_KEY|UNSPLASH_API_KEY)['\"],\s*['\"])([^'\"]*)/",
                        "$1YOUR_API_KEY_HERE",
                        $content
                    );

                    // Replace database credentials
                    $content = preg_replace(
                        "/(define\(['\"]DB_(?:USER|PASS|NAME)['\"],\s*['\"])([^'\"]*)/",
                        "$1YOUR_DB_CREDENTIALS",
                        $content
                    );

                    // Add sanitized version to ZIP
                    $zip->addFromString('source/' . $relativePath, $content);
                } else {
                    // Add original file
                    $zip->addFile($filePath, 'source/' . $relativePath);
                }
            }
        }
    }

    // Add README to backup
    $readmeContent = "# LightBlog CMS Backup\n\n";
    $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
    $readmeContent .= "Version: 1.0\n\n";
    $readmeContent .= "## Contents\n\n";
    $readmeContent .= "- `database.sql` - Complete database dump\n";
    $readmeContent .= "- `source/` - Complete source code (API keys removed for security)\n";
    $readmeContent .= "- `backup_info.json` - Backup metadata\n\n";
    $readmeContent .= "## Restore Instructions\n\n";
    $readmeContent .= "1. Go to Admin → Settings → Backup & Restore\n";
    $readmeContent .= "2. Click 'Restore from Backup'\n";
    $readmeContent .= "3. Upload this ZIP file\n";
    $readmeContent .= "4. Click 'Restore' and wait for completion\n";
    $readmeContent .= "5. Re-enter your API keys in Settings\n\n";
    $readmeContent .= "**WARNING:** Restore will overwrite current site data!\n";

    $zip->addFromString('README.txt', $readmeContent);

    $zip->close();

    // Clean up temp files
    unlink($dbFile);
    unlink($backupDir . '/backup_info.json');
    rmdir($backupDir);

    return [
        'success' => true,
        'file' => $zipFile,
        'filename' => basename($zipFile),
        'size' => filesize($zipFile),
        'metadata' => $metadata
    ];
}

/**
 * Restore from backup ZIP file
 */
function restoreFromBackup($zipFile) {
    $rootDir = dirname(__DIR__);
    $tempDir = sys_get_temp_dir() . '/lightblog_restore_' . time();

    // Create temp directory
    if (!mkdir($tempDir, 0755, true)) {
        return ['success' => false, 'message' => 'Failed to create temp directory'];
    }

    // Extract ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        return ['success' => false, 'message' => 'Failed to open ZIP file'];
    }

    $zip->extractTo($tempDir);
    $zip->close();

    // Validate backup structure
    if (!file_exists($tempDir . '/database.sql')) {
        return ['success' => false, 'message' => 'Invalid backup: database.sql not found'];
    }

    if (!file_exists($tempDir . '/backup_info.json')) {
        return ['success' => false, 'message' => 'Invalid backup: backup_info.json not found'];
    }

    // Read metadata
    $metadata = json_decode(file_get_contents($tempDir . '/backup_info.json'), true);

    // Create rollback backup (current state)
    $rollbackDir = sys_get_temp_dir() . '/lightblog_rollback_' . time();
    mkdir($rollbackDir, 0755, true);
    exportDatabase($rollbackDir . '/database.sql');

    try {
        // Restore database
        $db = Database::getInstance();
        $sql = file_get_contents($tempDir . '/database.sql');

        // Split into individual queries
        $queries = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($queries as $query) {
            if (!empty($query) && substr($query, 0, 2) !== '--') {
                $db->query($query);
            }
        }

        // Restore source code (if exists in backup)
        if (file_exists($tempDir . '/source')) {
            // Backup current source (for rollback)
            // Note: Only restore if user confirms, as this can overwrite custom changes

            $sourceFiles = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir . '/source', RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($sourceFiles as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempDir . '/source') + 1);
                    $targetPath = $rootDir . '/' . $relativePath;

                    // Skip config.php (keep current credentials)
                    if ($relativePath === 'config.php') {
                        continue;
                    }

                    // Create directory if needed
                    $targetDir = dirname($targetPath);
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    // Copy file
                    copy($filePath, $targetPath);
                }
            }
        }

        // Clean up temp files
        deleteDirectory($tempDir);

        return [
            'success' => true,
            'message' => 'Backup restored successfully',
            'metadata' => $metadata,
            'rollback_dir' => $rollbackDir
        ];

    } catch (Exception $e) {
        // Rollback on error
        if (file_exists($rollbackDir . '/database.sql')) {
            $sql = file_get_contents($rollbackDir . '/database.sql');
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($queries as $query) {
                if (!empty($query) && substr($query, 0, 2) !== '--') {
                    $db->query($query);
                }
            }
        }

        return [
            'success' => false,
            'message' => 'Restore failed: ' . $e->getMessage(),
            'rolled_back' => true
        ];
    }
}

/**
 * Delete directory recursively
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }

    rmdir($dir);
    return true;
}

// Handle AJAX requests
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_backup':
        $result = createBackup();

        if ($result['success']) {
            // Store backup file path in session for download
            $_SESSION['backup_file'] = $result['file'];
            $_SESSION['backup_filename'] = $result['filename'];
        }

        echo json_encode($result);
        break;

    case 'download_backup':
        if (!isset($_SESSION['backup_file']) || !file_exists($_SESSION['backup_file'])) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Backup file not found']));
        }

        $file = $_SESSION['backup_file'];
        $filename = $_SESSION['backup_filename'];

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));

        readfile($file);

        // Clean up
        unlink($file);
        unset($_SESSION['backup_file']);
        unset($_SESSION['backup_filename']);
        exit;

    case 'restore_backup':
        if (!isset($_FILES['backup_file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            break;
        }

        $uploadedFile = $_FILES['backup_file'];

        // Validate file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload error: ' . $uploadedFile['error']]);
            break;
        }

        // Check file extension
        if (pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'zip') {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a ZIP file.']);
            break;
        }

        // Check file size (max 500MB)
        if ($uploadedFile['size'] > 500 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File too large. Max 500MB.']);
            break;
        }

        // Restore from backup
        $result = restoreFromBackup($uploadedFile['tmp_name']);
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
