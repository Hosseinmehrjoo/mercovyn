<?php
/**
 * Blog card — expects $post (normalized row), optional $index, $cardVariant (default|compact).
 */
if (empty($post) || !is_array($post)) {
    return;
}
$index = (int) ($index ?? 0);
$cardVariant = $cardVariant ?? 'default';
$cardDelay = ($index * 0.08);
$title = ad_blog_localized_field($post, 'title');
$excerpt = ad_blog_localized_field($post, 'excerpt');
$cover = trim((string) ($post['cover'] ?? ''));
$coverAlt = trim((string) ($post['cover_alt'] ?? ''));
if ($coverAlt === '') {
    $coverAlt = $title;
}
$detailUrl = url('blog-single/' . ad_blog_post_slug($post, getCurrentLanguage()));
$dateLabel = ad_blog_format_date($post['published_at'] ?? '');
$categories = $post['categories'] ?? [];
$firstCat = $categories[0]['name'] ?? '';
$colClass = ($cardVariant ?? 'default') === 'compact' ? 'col-md-6 col-lg-4' : 'col-lg-6 col-xl-4';
?>
<div class="<?= $colClass ?> wow fadeInUp" data-wow-delay="<?= (float) $cardDelay ?>s">
    <article class="blog-card h-100<?= $cardVariant === 'compact' ? ' blog-card--compact' : '' ?>">
        <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="blog-card__media" aria-hidden="true" tabindex="-1">
            <?php if ($cover !== ''): ?>
                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($coverAlt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
            <?php else: ?>
                <div class="blog-card__media-placeholder" aria-hidden="true">
                    <i class="fas fa-newspaper"></i>
                </div>
            <?php endif; ?>
            <?php if ($firstCat !== ''): ?>
                <span class="blog-card__cat"><?= htmlspecialchars($firstCat) ?></span>
            <?php endif; ?>
        </a>
        <div class="blog-card__body">
            <?php if ($dateLabel !== ''): ?>
                <time class="blog-card__date" datetime="<?= htmlspecialchars(substr((string) ($post['published_at'] ?? ''), 0, 10)) ?>">
                    <?= htmlspecialchars($dateLabel) ?>
                </time>
            <?php endif; ?>
            <h<?= $cardVariant === 'compact' ? '3' : '2' ?> class="blog-card__title">
                <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($title) ?></a>
            </h<?= $cardVariant === 'compact' ? '3' : '2' ?>>
            <?php if ($excerpt !== '' && $cardVariant !== 'compact'): ?>
                <p class="blog-card__excerpt"><?= htmlspecialchars($excerpt) ?></p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="blog-card__link btn btn-primary border-secondary rounded-pill px-4 py-2">
                <?= __('read_more') ?> <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
            </a>
        </div>
    </article>
</div>