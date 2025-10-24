<?php
/**
 * Debug menu display - Check what's being rendered
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Cache.php';
require_once __DIR__ . '/core/Template.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Menu Display Debug</h1>";
echo "<style>
body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
pre { background: white; padding: 15px; border: 1px solid #ddd; overflow-x: auto; }
.ok { color: green; }
.error { color: red; }
table { background: white; border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background: #4CAF50; color: white; }
</style>";

$db = Database::getInstance();
Template::init();

// Check menus
echo "<h2>📋 Menus in Database</h2>";
$menus = $db->query("SELECT * FROM menus");

echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Location</th></tr>";
foreach ($menus as $menu) {
    echo "<tr>";
    echo "<td>{$menu->id}</td>";
    echo "<td>" . htmlspecialchars($menu->name) . "</td>";
    echo "<td>" . htmlspecialchars($menu->location) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check menu items
echo "<h2>🗂️ Menu Items in Database</h2>";
foreach ($menus as $menu) {
    echo "<h3>Menu: " . htmlspecialchars($menu->name) . " (Location: {$menu->location})</h3>";
    
    $items = $db->query("
        SELECT * FROM menu_items 
        WHERE menu_id = ? 
        ORDER BY parent_id, menu_order
    ", [$menu->id]);
    
    if (empty($items)) {
        echo "<p class='error'>No items found!</p>";
        continue;
    }
    
    echo "<table>";
    echo "<tr><th>Order</th><th>Title</th><th>Type</th><th>Object ID</th><th>Custom URL</th><th>Parent</th></tr>";
    foreach ($items as $item) {
        echo "<tr>";
        echo "<td>{$item->menu_order}</td>";
        echo "<td>" . htmlspecialchars($item->title) . "</td>";
        echo "<td>{$item->type}</td>";
        echo "<td>{$item->object_id}</td>";
        echo "<td>" . htmlspecialchars($item->custom_url ?: '-') . "</td>";
        echo "<td>{$item->parent_id}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test menu rendering
echo "<h2>🎨 Menu Rendering Test</h2>";

echo "<h3>Primary Menu (render_menu function):</h3>";
echo "<div style='background: white; padding: 20px; border: 1px solid #ddd;'>";
ob_start();
render_menu('primary', 'main-nav', 'nav-menu');
$menuHtml = ob_get_clean();

if (empty($menuHtml)) {
    echo "<p class='error'>❌ No menu output!</p>";
} else {
    echo "<p class='ok'>✅ Menu rendered successfully</p>";
    echo "<h4>HTML Output:</h4>";
    echo "<pre>" . htmlspecialchars($menuHtml) . "</pre>";
    
    echo "<h4>Visual Preview:</h4>";
    echo $menuHtml;
}
echo "</div>";

// Check cache
echo "<h2>🗄️ Cache Check</h2>";
$cache = new Cache();
$cacheKey = 'menu_primary';
$cachedMenu = $cache->get($cacheKey);

if ($cachedMenu) {
    echo "<p class='ok'>✅ Menu is cached</p>";
    echo "<pre>" . print_r($cachedMenu, true) . "</pre>";
    echo "<p><strong>Clear cache:</strong> Delete file: content/cache/menu_primary.cache</p>";
} else {
    echo "<p>No cache found (this is OK)</p>";
}

// Check Template.php functions
echo "<h2>🔧 Function Tests</h2>";

echo "<table>";
echo "<tr><th>Function</th><th>Result</th></tr>";

// has_menu
echo "<tr><td>has_menu('primary')</td>";
$hasPrimary = has_menu('primary');
echo "<td class='" . ($hasPrimary ? 'ok' : 'error') . "'>" . ($hasPrimary ? '✅ TRUE' : '❌ FALSE') . "</td>";
echo "</tr>";

// get_menu
echo "<tr><td>Template::get_menu('primary')</td>";
$menu = Template::get_menu('primary');
echo "<td class='" . ($menu ? 'ok' : 'error') . "'>" . ($menu ? '✅ Found: ' . $menu->name : '❌ NULL') . "</td>";
echo "</tr>";

// get_menu_items
if ($menu) {
    echo "<tr><td>Template::get_menu_items({$menu->id})</td>";
    $items = Template::get_menu_items($menu->id);
    echo "<td class='" . (count($items) > 0 ? 'ok' : 'error') . "'>" . count($items) . " items</td>";
    echo "</tr>";
}

echo "</table>";

// URLs check
echo "<h2>🔗 Menu Item URLs</h2>";
if (!empty($items)) {
    echo "<table>";
    echo "<tr><th>Title</th><th>Type</th><th>Generated URL</th></tr>";
    
    foreach ($items as $item) {
        $url = '';
        switch ($item->type) {
            case 'page':
                if ($item->object_id) {
                    $page = $db->queryOne("SELECT slug FROM pages WHERE id = ?", [$item->object_id]);
                    $url = $page ? (SITE_URL . BASE_PATH . $page->slug) : 'Page not found';
                }
                break;
            case 'category':
                if ($item->object_id) {
                    $cat = $db->queryOne("SELECT slug FROM categories WHERE id = ?", [$item->object_id]);
                    $url = $cat ? (SITE_URL . BASE_PATH . 'category/' . $cat->slug) : 'Category not found';
                }
                break;
            case 'custom':
                $url = $item->custom_url;
                break;
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item->title) . "</td>";
        echo "<td>{$item->type}</td>";
        echo "<td><a href='$url' target='_blank'>$url</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<hr>";
echo "<h2>✅ Next Steps:</h2>";
echo "<ol>";
echo "<li>If menu items exist in database but don't show: Clear cache</li>";
echo "<li>If has_menu returns FALSE: Check menu location in database</li>";
echo "<li>If URLs are wrong: Check SITE_URL and BASE_PATH in config.php</li>";
echo "<li>If render_menu outputs nothing: Check Template.php render_menu function</li>";
echo "</ol>";
?>
