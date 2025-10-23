<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= seo_title() ?: site_name() ?></title>

    <?php
    // Get current post if on single post page
    global $currentPost;
    $isPostPage = !empty($currentPost);
    ?>

    <!-- SEO Meta Tags -->
    <?php if ($isPostPage && !empty($currentPost->meta_description)): ?>
        <meta name="description" content="<?= htmlspecialchars($currentPost->meta_description) ?>">
    <?php endif; ?>

    <?php if ($isPostPage && !empty($currentPost->focus_keyword)): ?>
        <meta name="keywords" content="<?= htmlspecialchars($currentPost->focus_keyword) ?>">
    <?php endif; ?>

    <?php if ($isPostPage): ?>
        <meta name="robots" content="<?= htmlspecialchars($currentPost->meta_robots ?? 'index,follow') ?>">
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php if ($isPostPage): ?>
        <?php $canonicalUrl = !empty($currentPost->canonical_url) ? $currentPost->canonical_url : (SITE_URL . BASE_PATH . 'post/' . $currentPost->slug); ?>
        <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php endif; ?>

    <!-- Open Graph Tags -->
    <?php if ($isPostPage): ?>
        <meta property="og:type" content="article">
        <meta property="og:title" content="<?= htmlspecialchars($currentPost->og_title ?: $currentPost->title) ?>">
        <?php if (!empty($currentPost->og_description)): ?>
            <meta property="og:description" content="<?= htmlspecialchars($currentPost->og_description) ?>">
        <?php elseif (!empty($currentPost->meta_description)): ?>
            <meta property="og:description" content="<?= htmlspecialchars($currentPost->meta_description) ?>">
        <?php endif; ?>
        <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
        <?php if (!empty($currentPost->og_image)): ?>
            <meta property="og:image" content="<?= htmlspecialchars($currentPost->og_image) ?>">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
        <?php endif; ?>
        <meta property="og:site_name" content="<?= htmlspecialchars(site_name()) ?>">
        <?php if (!empty($currentPost->published_at)): ?>
            <meta property="article:published_time" content="<?= date('c', strtotime($currentPost->published_at)) ?>">
        <?php endif; ?>
        <?php if (!empty($currentPost->updated_at)): ?>
            <meta property="article:modified_time" content="<?= date('c', strtotime($currentPost->updated_at)) ?>">
        <?php endif; ?>
    <?php else: ?>
        <meta property="og:type" content="website">
        <meta property="og:title" content="<?= htmlspecialchars(site_name()) ?>">
        <meta property="og:url" content="<?= SITE_URL . BASE_PATH ?>">
    <?php endif; ?>

    <!-- Twitter Card Tags -->
    <?php if ($isPostPage): ?>
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?= htmlspecialchars($currentPost->twitter_title ?: $currentPost->title) ?>">
        <?php if (!empty($currentPost->twitter_description)): ?>
            <meta name="twitter:description" content="<?= htmlspecialchars($currentPost->twitter_description) ?>">
        <?php elseif (!empty($currentPost->meta_description)): ?>
            <meta name="twitter:description" content="<?= htmlspecialchars($currentPost->meta_description) ?>">
        <?php endif; ?>
        <?php if (!empty($currentPost->twitter_image)): ?>
            <meta name="twitter:image" content="<?= htmlspecialchars($currentPost->twitter_image) ?>">
        <?php elseif (!empty($currentPost->og_image)): ?>
            <meta name="twitter:image" content="<?= htmlspecialchars($currentPost->og_image) ?>">
        <?php endif; ?>
    <?php endif; ?>

    <!-- JSON-LD Structured Data -->
    <?php if ($isPostPage): ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "<?= htmlspecialchars($currentPost->schema_type ?? 'Article') ?>",
            "headline": "<?= htmlspecialchars($currentPost->title) ?>",
            "description": "<?= htmlspecialchars($currentPost->meta_description ?? '') ?>",
            "url": "<?= htmlspecialchars($canonicalUrl) ?>",
            <?php if (!empty($currentPost->og_image)): ?>
            "image": "<?= htmlspecialchars($currentPost->og_image) ?>",
            <?php endif; ?>
            <?php if (!empty($currentPost->published_at)): ?>
            "datePublished": "<?= date('c', strtotime($currentPost->published_at)) ?>",
            <?php endif; ?>
            <?php if (!empty($currentPost->updated_at)): ?>
            "dateModified": "<?= date('c', strtotime($currentPost->updated_at)) ?>",
            <?php endif; ?>
            <?php if (!empty($currentPost->word_count)): ?>
            "wordCount": <?= (int)$currentPost->word_count ?>,
            <?php endif; ?>
            "author": {
                "@type": "Person",
                "name": "<?= htmlspecialchars($currentPost->author_name ?? site_name()) ?>"
            },
            "publisher": {
                "@type": "Organization",
                "name": "<?= htmlspecialchars(site_name()) ?>",
                "logo": {
                    "@type": "ImageObject",
                    "url": "<?= SITE_URL . BASE_PATH ?>logo.png"
                }
            }
        }
        </script>

        <?php if (!empty($currentPost->faq_data) && ($currentPost->schema_type === 'FAQPage' || strpos($currentPost->faq_data, '"@type":"FAQPage"') !== false)): ?>
        <!-- FAQ Schema -->
        <script type="application/ld+json">
        <?= $currentPost->faq_data ?>
        </script>
        <?php endif; ?>
    <?php endif; ?>

    <?php wp_head(); ?>

    <link rel="stylesheet" href="<?= theme_url('/style.css') ?>">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="site-branding">
                <h1 class="site-title">
                    <a href="<?= SITE_URL ?>"><?= site_name() ?></a>
                </h1>
                <p class="site-tagline"><?= site_tagline() ?></p>
            </div>

            <?php if (has_menu('primary')): ?>
                <?php render_menu('primary', 'main-nav', 'nav-menu'); ?>
            <?php else: ?>
                <!-- Fallback navigation if no menu is configured -->
                <nav class="main-nav">
                    <ul class="nav-menu">
                        <li><a href="<?= SITE_URL . BASE_PATH ?>">Home</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="site-content">
