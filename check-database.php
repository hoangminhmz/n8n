<?php
/**
 * Check database tables and data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Check</h1><pre>";

try {
    echo "Loading config...\n";
    require_once __DIR__ . '/config.php';
    echo "✓ Config loaded\n\n";

    echo "Connecting to database...\n";
    require_once __DIR__ . '/core/Database.php';
    $db = Database::getInstance();
    echo "✓ Database connected\n\n";

    // Check settings table
    echo "=== SETTINGS TABLE ===\n";
    try {
        $settings = $db->query("SELECT * FROM settings");
        echo "Found " . count($settings) . " settings:\n";
        foreach ($settings as $setting) {
            echo "  - {$setting->key} = " . htmlspecialchars($setting->value) . "\n";
        }

        // Check for required settings
        $required = ['site_name', 'site_tagline'];
        echo "\nRequired settings check:\n";
        foreach ($required as $key) {
            $exists = false;
            foreach ($settings as $s) {
                if ($s->key === $key) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                echo "  ✓ $key exists\n";
            } else {
                echo "  ✗ $key MISSING\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ ERROR querying settings: " . $e->getMessage() . "\n";
    }

    echo "\n=== POSTS TABLE ===\n";
    try {
        $posts = $db->query("SELECT id, title, slug, status FROM posts");
        echo "Found " . count($posts) . " posts:\n";
        foreach ($posts as $post) {
            echo "  - ID {$post->id}: " . htmlspecialchars($post->title) . " ({$post->status})\n";
        }
    } catch (Exception $e) {
        echo "✗ ERROR querying posts: " . $e->getMessage() . "\n";
    }

    echo "\n=== USERS TABLE ===\n";
    try {
        $users = $db->query("SELECT id, username, email FROM users");
        echo "Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "  - ID {$user->id}: " . htmlspecialchars($user->username) . " (" . htmlspecialchars($user->email) . ")\n";
        }
    } catch (Exception $e) {
        echo "✗ ERROR querying users: " . $e->getMessage() . "\n";
    }

    echo "\n=== FIX MISSING SETTINGS ===\n";
    // Check if settings are missing and offer to fix
    $settings_array = $db->query("SELECT * FROM settings");
    $has_site_name = false;
    $has_site_tagline = false;

    foreach ($settings_array as $s) {
        if ($s->key === 'site_name') $has_site_name = true;
        if ($s->key === 'site_tagline') $has_site_tagline = true;
    }

    if (!$has_site_name || !$has_site_tagline) {
        echo "Some required settings are missing!\n\n";
        echo "To fix, run these SQL commands:\n\n";

        if (!$has_site_name) {
            echo "INSERT INTO settings (`key`, value) VALUES ('site_name', 'My Blog');\n";
        }
        if (!$has_site_tagline) {
            echo "INSERT INTO settings (`key`, value) VALUES ('site_tagline', 'Just another LightBlog site');\n";
        }

        echo "\nOr click here to auto-fix: ";
        if (isset($_GET['fix'])) {
            if (!$has_site_name) {
                $db->query("INSERT INTO settings (`key`, value) VALUES ('site_name', 'My Blog')");
                echo "✓ Added site_name\n";
            }
            if (!$has_site_tagline) {
                $db->query("INSERT INTO settings (`key`, value) VALUES ('site_tagline', 'Just another LightBlog site')");
                echo "✓ Added site_tagline\n";
            }
            echo "\n✓ Settings fixed! <a href='?'>Refresh to check</a>\n";
        } else {
            echo "<a href='?fix=1' style='color: blue; text-decoration: underline;'>Click here to auto-fix settings</a>\n";
        }
    } else {
        echo "✓ All required settings present!\n";
    }

} catch (Exception $e) {
    echo "\n\nFATAL ERROR:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
