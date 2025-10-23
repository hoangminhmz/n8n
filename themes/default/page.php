<?php get_header(); ?>

<div class="page-content">
    <article class="page">
        <header class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($page->title) ?></h1>

            <?php if (!empty($page->excerpt)): ?>
                <div class="page-excerpt">
                    <?= nl2br(htmlspecialchars($page->excerpt)) ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="page-body">
            <?php
            // Output page content (already HTML)
            echo $page->content;
            ?>
        </div>

        <?php if (!empty($page->custom_css)): ?>
            <style>
                <?= $page->custom_css ?>
            </style>
        <?php endif; ?>

        <?php if (!empty($page->custom_js)): ?>
            <script>
                <?= $page->custom_js ?>
            </script>
        <?php endif; ?>
    </article>
</div>

<?php get_footer(); ?>
