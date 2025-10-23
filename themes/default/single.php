<?php get_header(); ?>

<article class="post-single">
    <header class="post-header">
        <h1 class="post-title"><?= the_title() ?></h1>

        <div class="post-meta">
            <time datetime="<?= get_date('c') ?>">
                <?= get_date('F j, Y') ?>
            </time>
            <span class="separator">•</span>
            <span class="reading-time"><?= reading_time() ?> min read</span>
            <?php global $currentPost; if (!empty($currentPost->word_count)): ?>
                <span class="separator">•</span>
                <span class="word-count"><?= number_format($currentPost->word_count) ?> words</span>
            <?php endif; ?>
            <span class="separator">•</span>
            <span class="view-count"><?= view_count() ?> views</span>
        </div>
    </header>

    <?php if (has_featured_image()): ?>
        <div class="featured-image">
            <img src="<?= featured_image_url() ?>"
                 alt="<?= the_title() ?>"
                 loading="eager">
        </div>
    <?php endif; ?>

    <div class="post-content">
        <?= the_content() ?>
    </div>

    <?php if (has_tags()): ?>
        <div class="post-tags">
            <?php foreach (get_tags() as $tag): ?>
                <a href="<?= tag_url($tag->slug) ?>" class="tag">
                    #<?= $tag->name ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (has_related_posts()): ?>
        <section class="related-posts">
            <h2>Related Articles</h2>
            <div class="posts-grid">
                <?php foreach (get_related_posts(3) as $related): ?>
                    <article class="post-card">
                        <a href="<?= post_url($related->slug) ?>">
                            <?php if ($related->featured_image): ?>
                                <img src="<?= $related->featured_image ?>"
                                     alt="<?= htmlspecialchars($related->title) ?>"
                                     loading="lazy">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($related->title) ?></h3>
                            <p><?= excerpt($related->excerpt ?: $related->content, 100) ?></p>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
