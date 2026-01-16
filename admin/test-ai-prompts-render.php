<?php
/**
 * Test AI Prompts Rendering - Step by Step
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Test AI Prompts Render';

echo "STEP 1: Including header...<br>";
flush();
require_once __DIR__ . '/includes/header.php';
echo "✓ Header loaded<br>";
flush();

echo "STEP 2: Loading PromptManager...<br>";
flush();
require_once SITE_PATH . '/core/AI/PromptManager.php';
$promptManager = new PromptManager();
echo "✓ PromptManager created<br>";
flush();

echo "STEP 3: Getting templates...<br>";
flush();
$postTemplates = $promptManager->getTemplatesByCategory('post');
$campaignTemplates = $promptManager->getTemplatesByCategory('campaign');
$imageTemplates = $promptManager->getTemplatesByCategory('image');
echo "✓ Got templates: " . count($postTemplates) . " post, " . count($campaignTemplates) . " campaign, " . count($imageTemplates) . " image<br>";
flush();

echo "STEP 4: Getting stats...<br>";
flush();
$stats = $promptManager->getStatistics();
echo "✓ Got stats<br>";
flush();

echo "<hr>";
echo "STEP 5: Rendering simple version...<br>";
flush();
?>

<div class="card">
    <h2>Post Templates (<?= count($postTemplates) ?>)</h2>

    <?php foreach ($postTemplates as $index => $template):
        echo "Rendering template #" . ($index + 1) . ": " . htmlspecialchars($template->template_name ?? 'UNNAMED') . "<br>";
        flush();

        $variables = json_decode($template->variables ?? '[]', true) ?? [];
        $currentPrompt = ($template->is_active && !empty($template->custom_prompt))
            ? $template->custom_prompt
            : $template->default_prompt;
    ?>
        <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
            <h3><?= htmlspecialchars($template->template_name ?? 'Unnamed') ?></h3>
            <p><strong>Key:</strong> <?= htmlspecialchars($template->template_key ?? 'N/A') ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($template->description ?? 'No description') ?></p>
            <p><strong>Variables:</strong> <?= count($variables) ?> variables</p>
            <p><strong>Active:</strong> <?= $template->is_active ? 'Yes' : 'No' ?></p>
            <p><strong>Prompt length:</strong> <?= strlen($currentPrompt) ?> chars</p>
        </div>
    <?php endforeach; ?>
</div>

<hr>
<h2>Campaign Templates (<?= count($campaignTemplates) ?>)</h2>
<?php foreach ($campaignTemplates as $index => $template):
    echo "Campaign template #" . ($index + 1) . ": " . htmlspecialchars($template->template_name ?? 'UNNAMED') . "<br>";
    flush();
endforeach; ?>

<hr>
<h2>Image Templates (<?= count($imageTemplates) ?>)</h2>
<?php foreach ($imageTemplates as $index => $template):
    echo "Image template #" . ($index + 1) . ": " . htmlspecialchars($template->template_name ?? 'UNNAMED') . "<br>";
    flush();
endforeach; ?>

<hr>
<p>✅ ALL RENDERING COMPLETE!</p>

<?php
echo "<br>STEP 6: Including footer...<br>";
flush();
require_once __DIR__ . '/includes/footer.php';
echo "✓ Footer loaded<br>";
?>
