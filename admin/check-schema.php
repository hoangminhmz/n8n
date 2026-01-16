<?php
/**
 * Check Database Schema for prompt_templates
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';

$db = Database::getInstance();

echo "<h2>Checking prompt_templates table structure</h2>";

try {
    // Get table structure
    $columns = $db->query("DESCRIBE prompt_templates");

    echo "<h3>Columns in prompt_templates table:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

    $hasCustomPrompt = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col->Field) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col->Type) . "</td>";
        echo "<td>" . htmlspecialchars($col->Null) . "</td>";
        echo "<td>" . htmlspecialchars($col->Key ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col->Default ?? 'NULL') . "</td>";
        echo "</tr>";

        if ($col->Field === 'custom_prompt') {
            $hasCustomPrompt = true;
        }
    }
    echo "</table>";

    if ($hasCustomPrompt) {
        echo "<p style='color: green;'>✓ <strong>custom_prompt</strong> column EXISTS!</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>custom_prompt</strong> column is MISSING!</p>";
        echo "<p>This is the problem! The column needs to be added to the database.</p>";
    }

    // Test query
    echo "<h3>Testing SELECT query:</h3>";
    $test = $db->queryOne("SELECT * FROM prompt_templates LIMIT 1");

    if ($test) {
        echo "<h4>Object properties:</h4><pre>";
        print_r($test);
        echo "</pre>";

        echo "<h4>Checking specific property:</h4>";
        echo "isset(\$test->custom_prompt): " . (isset($test->custom_prompt) ? 'TRUE' : 'FALSE') . "<br>";
        echo "property_exists(\$test, 'custom_prompt'): " . (property_exists($test, 'custom_prompt') ? 'TRUE' : 'FALSE') . "<br>";

        if (property_exists($test, 'custom_prompt')) {
            echo "Value: " . ($test->custom_prompt === null ? 'NULL' : htmlspecialchars($test->custom_prompt)) . "<br>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
