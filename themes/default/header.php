<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= seo_title() ?: site_name() ?></title>

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

            <nav class="main-nav">
                <a href="<?= SITE_URL ?>">Home</a>
                <!-- Add more navigation items -->
            </nav>
        </div>
    </header>

    <main class="site-content">
