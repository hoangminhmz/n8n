<?php get_header(); ?>

<div class="container">
    <div class="archive-page">
    <h1>
        <?php if (isset($category)): ?>
            Category: <?= htmlspecialchars($category->name) ?>
        <?php elseif (isset($tag)): ?>
            Tag: <?= htmlspecialchars($tag->name) ?>
        <?php else: ?>
            Archive
        <?php endif; ?>
    </h1>

    <?php if (empty($posts)): ?>
        <p>No posts found.</p>
    <?php else: ?>
        <div class="posts-list">
            <?php foreach ($posts as $post): ?>
                <article class="post-summary">
                    <h2>
                        <a href="<?= SITE_URL . '/post/' . $post->slug ?>">
                            <?= htmlspecialchars($post->title) ?>
                        </a>
                    </h2>

                    <div class="post-meta">
                        <time datetime="<?= $post->published_at ?>">
                            <?= date('F j, Y', strtotime($post->published_at)) ?>
                        </time>
                    </div>

                    <p><?= excerpt($post->excerpt ?: $post->content, 200) ?></p>

                    <a href="<?= SITE_URL . '/post/' . $post->slug ?>" class="read-more">
                        Read More →
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
