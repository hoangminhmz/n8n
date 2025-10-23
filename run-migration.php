<?php
/**
 * Run database migrations
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance();

echo "=== Running Pages Migration ===\n\n";

// Read migration file
$sql = file_get_contents(__DIR__ . '/install/migrate-pages.sql');

// Split by semicolons to get individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    // Skip empty statements and comments
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }

    try {
        $db->query($statement);
        $success++;
        echo "✓ Executed statement\n";
    } catch (Exception $e) {
        $errors++;
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n=== Migration Complete ===\n";
echo "Success: $success\n";
echo "Errors: $errors\n";

// Verify tables exist
echo "\n=== Verifying Tables ===\n";
try {
    $result = $db->query("SHOW TABLES LIKE 'pages'");
    if ($result) {
        echo "✓ Table 'pages' exists\n";

        // Count rows
        $count = $db->queryOne("SELECT COUNT(*) as count FROM pages");
        echo "  → Contains " . $count->count . " page(s)\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking table: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
