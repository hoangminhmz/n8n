<?php
/**
 * LightBlog CMS - Campaign Management
 */

require_once __DIR__ . '/../config.php';
require_once SITE_PATH . '/core/Database.php';
require_once SITE_PATH . '/core/Auth.php';
require_once SITE_PATH . '/core/AutoBlog/Campaign.php';

$pageTitle = 'AI Campaigns';
$auth = new Auth();
$auth->requireLogin();

$campaignManager = new Campaign();
$action = $_GET['action'] ?? 'list';
$campaignId = $_GET['id'] ?? null;
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_campaign'])) {
        $seedKeywords = array_filter(array_map('trim', explode("\n", $_POST['seed_keywords'])));

        $campaignId = $campaignManager->create([
            'name' => $_POST['name'],
            'niche' => $_POST['niche'],
            'goal' => $_POST['goal'],
            'seed_keywords' => $seedKeywords,
            'target_count' => (int)$_POST['target_count'],
            'posts_per_day' => (int)$_POST['posts_per_day'],
            'content_types' => $_POST['content_types'] ?? ['article'],
            'word_count_min' => (int)$_POST['word_count_min'],
            'word_count_max' => (int)$_POST['word_count_max'],
            'ai_provider' => $_POST['ai_provider'],
            'ai_model' => $_POST['ai_model'],
            'ai_temperature' => (float)$_POST['ai_temperature'],
            'tone' => $_POST['tone'],
            'language' => $_POST['language'],
            'start_date' => $_POST['start_date'],
            'publish_times' => array_filter(array_map('trim', explode(',', $_POST['publish_times'])))
        ]);

        $message = 'Campaign created successfully!';
        $action = 'view';
    } elseif (isset($_POST['generate_topics'])) {
        // Generate topics for preview
        $topicCount = (int)$_POST['topic_count'];
        $generatedTopics = $campaignManager->generateTopics($_POST['campaign_id'], $topicCount);
        $action = 'preview_topics';
        // Store topics in session for confirmation
        $_SESSION['preview_topics'] = $generatedTopics;
        $_SESSION['preview_campaign_id'] = $_POST['campaign_id'];
    } elseif (isset($_POST['confirm_queue'])) {
        // Queue the previewed topics
        $topics = $_SESSION['preview_topics'] ?? [];
        if (!empty($topics)) {
            $queued = $campaignManager->queueTopics($_POST['campaign_id'], $topics);
            $message = "✅ Successfully queued {$queued} AI-generated topics!";
            unset($_SESSION['preview_topics']);
            unset($_SESSION['preview_campaign_id']);
        }
        $action = 'view';
    } elseif (isset($_POST['regenerate_topics'])) {
        // Regenerate topics
        $topicCount = (int)$_POST['topic_count'];
        $generatedTopics = $campaignManager->generateTopics($_POST['campaign_id'], $topicCount);
        $action = 'preview_topics';
        $_SESSION['preview_topics'] = $generatedTopics;
        $_SESSION['preview_campaign_id'] = $_POST['campaign_id'];
    } elseif (isset($_POST['pause_campaign'])) {
        $campaignManager->pause($_POST['campaign_id']);
        $message = 'Campaign paused successfully!';
        $action = 'list';
    } elseif (isset($_POST['resume_campaign'])) {
        $campaignManager->resume($_POST['campaign_id']);
        $message = 'Campaign resumed successfully!';
        $action = 'list';
    } elseif (isset($_POST['delete_campaign'])) {
        $campaignManager->delete($_POST['campaign_id']);
        $message = 'Campaign deleted successfully!';
        $action = 'list';
        $campaignId = null;
    } elseif (isset($_POST['generate_now'])) {
        $result = $campaignManager->generateNow($_POST['campaign_id'], $_POST['topic'] ?? null);
        if ($result['success']) {
            $message = 'Content generation started! Topic: ' . htmlspecialchars($result['topic']);
        } else {
            $message = 'Error: ' . $result['message'];
        }
    }
}

// Get campaign data
$campaign = $campaignId ? $campaignManager->get($campaignId) : null;
$campaigns = $action === 'list' ? $campaignManager->getAll() : [];

include __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Campaign List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Campaigns</h2>
            <a href="?action=new" class="btn btn-primary">+ New Campaign</a>
        </div>

        <?php if (empty($campaigns)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🤖</div>
                <h3>No Campaigns Yet</h3>
                <p>Create your first AI auto-blogging campaign</p>
                <a href="?action=new" class="btn btn-primary">Create Campaign</a>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1.5rem;">
                <?php foreach ($campaigns as $camp): ?>
                    <?php $progress = $campaignManager->getProgress($camp->id); ?>
                    <div class="card" style="background: var(--bg); border: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin: 0 0 0.5rem 0;">
                                    🤖 <?= htmlspecialchars($camp->name) ?>
                                </h3>
                                <p style="color: var(--text-light); margin: 0;">
                                    <?= htmlspecialchars($camp->niche) ?> •
                                    <?= $camp->ai_provider ?>/<?= $camp->ai_model ?> •
                                    <?= $camp->posts_per_day ?> posts/day
                                </p>
                            </div>
                            <span class="badge badge-<?= $camp->status === 'active' ? 'success' : 'warning' ?>">
                                <?= $camp->status ?>
                            </span>
                        </div>

                        <div class="progress" style="margin-bottom: 0.5rem;">
                            <div class="progress-bar" style="width: <?= $progress['percentage'] ?>%"></div>
                        </div>
                        <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 1rem;">
                            <?= $progress['published'] ?> / <?= $progress['target'] ?> posts (<?= $progress['percentage'] ?>%)
                        </p>

                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="?action=view&id=<?= $camp->id ?>" class="btn btn-sm btn-primary">Manage</a>
                            <a href="<?= BASE_PATH ?>admin/queue.php?campaign=<?= $camp->id ?>" class="btn btn-sm btn-outline">View Queue</a>

                            <?php if ($camp->status === 'active'): ?>
                                <form method="POST" style="display: inline; margin: 0;">
                                    <input type="hidden" name="campaign_id" value="<?= $camp->id ?>">
                                    <button type="submit" name="pause_campaign" class="btn btn-sm btn-warning" onclick="return confirm('Pause this campaign?')">⏸ Pause</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline; margin: 0;">
                                    <input type="hidden" name="campaign_id" value="<?= $camp->id ?>">
                                    <button type="submit" name="resume_campaign" class="btn btn-sm btn-success">▶ Resume</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display: inline; margin: 0;">
                                <input type="hidden" name="campaign_id" value="<?= $camp->id ?>">
                                <button type="submit" name="delete_campaign" class="btn btn-sm btn-danger" onclick="return confirm('Delete this campaign? This cannot be undone!')">🗑 Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new'): ?>
    <!-- Create Campaign -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Create New Campaign</h2>
            <a href="?action=list" class="btn btn-outline">← Back</a>
        </div>

        <form method="POST">
            <h3 style="margin-bottom: 1rem;">Campaign Basics</h3>

            <div class="form-group">
                <label>Campaign Name *</label>
                <input type="text" name="name" required placeholder="e.g., Tech Reviews 2025">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Niche *</label>
                    <input type="text" name="niche" required placeholder="e.g., Technology">
                </div>
                <div class="form-group">
                    <label>Goal *</label>
                    <select name="goal">
                        <option value="traffic">Traffic</option>
                        <option value="affiliate_sales">Affiliate Sales</option>
                        <option value="authority">Authority Building</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Seed Keywords * (one per line)</label>
                <textarea name="seed_keywords" rows="5" required placeholder="best laptop&#10;laptop review&#10;gaming laptop"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Target Posts *</label>
                    <input type="number" name="target_count" value="50" min="1" required>
                </div>
                <div class="form-group">
                    <label>Posts Per Day *</label>
                    <input type="number" name="posts_per_day" value="3" min="1" max="20" required>
                </div>
            </div>

            <hr style="margin: 2rem 0;">
            <h3 style="margin-bottom: 1rem;">AI Configuration</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>AI Provider *</label>
                    <select name="ai_provider" id="ai_provider" required onchange="updateModelOptions()">
                        <option value="openai">OpenAI</option>
                        <option value="claude">Anthropic Claude</option>
                        <option value="gemini">Google Gemini</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <select name="ai_model" id="ai_model" required>
                        <optgroup label="OpenAI" class="models-openai">
                            <option value="gpt-4">GPT-4 (Most capable, higher cost)</option>
                            <option value="gpt-4-turbo">GPT-4 Turbo (Fast and capable)</option>
                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Fast and economical)</option>
                        </optgroup>
                        <optgroup label="Claude" class="models-claude" style="display:none;">
                            <option value="claude-3-opus-20240229">Claude 3 Opus (Most intelligent)</option>
                            <option value="claude-3-sonnet-20240229">Claude 3 Sonnet (Balanced)</option>
                            <option value="claude-3-haiku-20240307">Claude 3 Haiku (Fast)</option>
                        </optgroup>
                        <optgroup label="Gemini" class="models-gemini" style="display:none;">
                            <option value="gemini-2.5-flash" selected>Gemini 2.5 Flash (Newest, fastest, recommended)</option>
                            <option value="gemini-2.0-flash-exp">Gemini 2.0 Flash Experimental</option>
                            <option value="gemini-1.5-pro">Gemini 1.5 Pro (Most capable, multimodal)</option>
                            <option value="gemini-1.5-flash">Gemini 1.5 Flash (Fast and efficient)</option>
                            <option value="gemini-pro">Gemini Pro (Legacy)</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <script>
            function updateModelOptions() {
                const provider = document.getElementById('ai_provider').value;
                const modelSelect = document.getElementById('ai_model');
                const optgroups = modelSelect.querySelectorAll('optgroup');

                // Hide all optgroups
                optgroups.forEach(group => {
                    group.style.display = 'none';
                    group.querySelectorAll('option').forEach(opt => opt.disabled = true);
                });

                // Show selected provider's optgroup
                const activeGroup = modelSelect.querySelector('.models-' + provider);
                if (activeGroup) {
                    activeGroup.style.display = 'block';
                    activeGroup.querySelectorAll('option').forEach(opt => opt.disabled = false);
                    // Select first option in the active group
                    const firstOption = activeGroup.querySelector('option');
                    if (firstOption) firstOption.selected = true;
                }
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', updateModelOptions);
            </script>

            <div class="form-row">
                <div class="form-group">
                    <label>Tone *</label>
                    <select name="tone">
                        <option value="professional">Professional</option>
                        <option value="casual">Casual</option>
                        <option value="expert">Expert</option>
                        <option value="friendly">Friendly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Language *</label>
                    <select name="language">
                        <option value="en">English</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Word Count Min *</label>
                    <input type="number" name="word_count_min" value="1500" required>
                </div>
                <div class="form-group">
                    <label>Word Count Max *</label>
                    <input type="number" name="word_count_max" value="2500" required>
                </div>
            </div>

            <div class="form-group">
                <label>Temperature (0-1) *</label>
                <input type="number" name="ai_temperature" value="0.7" step="0.1" min="0" max="1" required>
                <small>Higher = more creative, Lower = more focused</small>
            </div>

            <hr style="margin: 2rem 0;">
            <h3 style="margin-bottom: 1rem;">Publishing Schedule</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Publish Times * (comma-separated, 24h format)</label>
                    <input type="text" name="publish_times" value="09:00, 14:00, 20:00" required>
                    <small>e.g., 09:00, 14:00, 20:00</small>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" name="create_campaign" class="btn btn-primary">Create Campaign</button>
                <a href="?action=list" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'view' && $campaign): ?>
    <!-- View/Manage Campaign -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🤖 <?= htmlspecialchars($campaign->name) ?></h2>
            <a href="?action=list" class="btn btn-outline">← Back</a>
        </div>

        <?php $progress = $campaignManager->getProgress($campaign->id); ?>

        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div>
                <div class="stat-label">Progress</div>
                <div class="stat-value"><?= $progress['percentage'] ?>%</div>
            </div>
            <div>
                <div class="stat-label">Published</div>
                <div class="stat-value"><?= $progress['published'] ?></div>
            </div>
            <div>
                <div class="stat-label">Target</div>
                <div class="stat-value"><?= $progress['target'] ?></div>
            </div>
        </div>

        <div class="progress">
            <div class="progress-bar" style="width: <?= $progress['percentage'] ?>%"></div>
        </div>

        <hr style="margin: 2rem 0;">

        <h3 style="margin-bottom: 1rem;">🤖 AI-Powered Topic Generation</h3>
        <form method="POST" style="display: flex; gap: 1rem; align-items: end;">
            <input type="hidden" name="campaign_id" value="<?= $campaign->id ?>">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label>Number of Topics to Generate</label>
                <input type="number" name="topic_count" value="10" min="1" max="50">
            </div>
            <button type="submit" name="generate_topics" class="btn btn-primary">🧠 Generate Topics with AI</button>
        </form>

        <div class="alert alert-success" style="margin-top: 1rem;">
            <strong>✨ Smart AI Topic Generation</strong><br>
            Uses <strong><?= htmlspecialchars($campaign->ai_provider) ?> (<?= htmlspecialchars($campaign->ai_model) ?>)</strong> to create diverse, SEO-optimized topics based on your:
            <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                <li>Seed keywords: <strong><?= htmlspecialchars(implode(', ', json_decode($campaign->seed_keywords, true) ?? [])) ?></strong></li>
                <li>Campaign goal: <strong><?= htmlspecialchars($campaign->goal) ?></strong></li>
                <li>Niche: <strong><?= htmlspecialchars($campaign->niche) ?></strong></li>
            </ul>
            You'll be able to review and approve topics before queueing them.
        </div>

        <hr style="margin: 2rem 0;">

        <h3 style="margin-bottom: 1rem;">⚡ Generate Content Now (Manual)</h3>
        <form method="POST" style="display: flex; gap: 1rem; align-items: end;">
            <input type="hidden" name="campaign_id" value="<?= $campaign->id ?>">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label>Topic (Optional - leave blank to auto-generate)</label>
                <input type="text" name="topic" placeholder="e.g., Best Laptops in <?= date('Y') ?>">
            </div>
            <button type="submit" name="generate_now" class="btn btn-success">🚀 Generate Now</button>
        </form>

        <div class="alert alert-warning" style="margin-top: 1rem;">
            ⚡ <strong>Instant Generation:</strong> This will queue content with high priority for immediate processing.
            The AI will generate content based on your campaign settings. Check the
            <a href="<?= BASE_PATH ?>admin/queue.php?campaign=<?= $campaign->id ?>">queue</a> to monitor progress.
        </div>

        <hr style="margin: 2rem 0;">

        <h3 style="margin-bottom: 1rem;">Campaign Actions</h3>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <?php if ($campaign->status === 'active'): ?>
                <form method="POST" style="display: inline; margin: 0;">
                    <input type="hidden" name="campaign_id" value="<?= $campaign->id ?>">
                    <button type="submit" name="pause_campaign" class="btn btn-warning" onclick="return confirm('Pause this campaign?')">⏸ Pause Campaign</button>
                </form>
            <?php else: ?>
                <form method="POST" style="display: inline; margin: 0;">
                    <input type="hidden" name="campaign_id" value="<?= $campaign->id ?>">
                    <button type="submit" name="resume_campaign" class="btn btn-success">▶ Resume Campaign</button>
                </form>
            <?php endif; ?>

            <form method="POST" style="display: inline; margin: 0;">
                <input type="hidden" name="campaign_id" value="<?= $campaign->id ?>">
                <button type="submit" name="delete_campaign" class="btn btn-danger" onclick="return confirm('Delete this campaign and all associated data? This cannot be undone!')">🗑 Delete Campaign</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'preview_topics'): ?>
    <!-- Preview Generated Topics -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🤖 AI-Generated Topics Preview</h2>
            <a href="?action=view&id=<?= $_SESSION['preview_campaign_id'] ?>" class="btn btn-outline">Cancel</a>
        </div>

        <div class="alert alert-success">
            <strong>✨ Topics generated successfully!</strong><br>
            These topics were intelligently created by AI based on your campaign's seed keywords, niche, and goals.
            Review them below and click "Queue All Topics" to proceed, or "Regenerate" to create new ones.
        </div>

        <?php
        $previewTopics = $_SESSION['preview_topics'] ?? [];
        $previewCampaignId = $_SESSION['preview_campaign_id'] ?? null;
        $previewCampaign = $previewCampaignId ? $campaignManager->get($previewCampaignId) : null;
        ?>

        <?php if ($previewCampaign): ?>
        <div style="background: #f9fafb; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <strong>Campaign:</strong> <?= htmlspecialchars($previewCampaign->name) ?> |
            <strong>Niche:</strong> <?= htmlspecialchars($previewCampaign->niche) ?> |
            <strong>Goal:</strong> <?= htmlspecialchars($previewCampaign->goal) ?> |
            <strong>AI:</strong> <?= htmlspecialchars($previewCampaign->ai_provider) ?> (<?= htmlspecialchars($previewCampaign->ai_model) ?>)
        </div>
        <?php endif; ?>

        <div style="margin-bottom: 1.5rem;">
            <h3 style="margin-bottom: 1rem;">Generated Topics (<?= count($previewTopics) ?>)</h3>
            <div style="display: grid; gap: 0.75rem;">
                <?php foreach ($previewTopics as $index => $topic): ?>
                <div style="display: flex; align-items: center; padding: 1rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; min-width: 35px; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-weight: 600; font-size: 0.875rem;">
                        <?= $index + 1 ?>
                    </div>
                    <div style="flex: 1; font-size: 1rem; color: #374151;">
                        <?= htmlspecialchars($topic) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
            <div style="display: flex; gap: 0.5rem;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="campaign_id" value="<?= $previewCampaignId ?>">
                    <button type="submit" name="confirm_queue" class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 2rem;">
                        ✅ Queue All Topics
                    </button>
                </form>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="campaign_id" value="<?= $previewCampaignId ?>">
                    <input type="hidden" name="topic_count" value="<?= count($previewTopics) ?>">
                    <button type="submit" name="regenerate_topics" class="btn btn-outline" style="font-size: 1rem;">
                        🔄 Regenerate Different Topics
                    </button>
                </form>
            </div>

            <a href="?action=view&id=<?= $previewCampaignId ?>" class="btn btn-outline">Cancel</a>
        </div>

        <div class="alert alert-info" style="margin-top: 1.5rem;">
            💡 <strong>What happens next?</strong><br>
            When you queue these topics, they will be scheduled according to your campaign's publishing schedule.
            The AI will then generate full articles for each topic when the cron job runs.
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
