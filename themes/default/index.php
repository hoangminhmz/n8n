<?php get_header(); ?>

<div class="container">
    <div class="homepage">
        <h1>Latest Posts</h1>

        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <a href="<?= SITE_URL . '/post/' . $post->slug ?>">
                        <?php if ($post->featured_image): ?>
                            <img src="<?= $post->featured_image ?>"
                                 alt="<?= htmlspecialchars($post->title) ?>"
                                 loading="lazy">
                        <?php endif; ?>

                        <div class="post-card-content">
                            <h2><?= htmlspecialchars($post->title) ?></h2>

                            <div class="post-meta">
                                <time datetime="<?= $post->published_at ?>">
                                    <?= date('F j, Y', strtotime($post->published_at)) ?>
                                </time>
                            </div>

                            <p><?= excerpt($post->excerpt ?: $post->content, 150) ?></p>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
