<?php
/**
 * Blog single post page - fetches directly from database with multilingual support
 */
require_once __DIR__ . '/backend/lib/db.php';
require_once __DIR__ . '/backend/lib/helpers.php';
require_once __DIR__ . '/backend/lib/media.php';
require_once __DIR__ . '/backend/lib/blog.php';
require_once __DIR__ . '/router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

ad_blog_ensure_schema();

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = null;
$relatedPosts = [];

if ($slug !== '') {
    try {
        $db = ad_db();
        $stmt = $db->prepare(
            'SELECT id, title, slug, meta_title, meta_description, meta_keywords, excerpt, content, cover, published_at,
                    title_ru, title_hy, title_ar, slug_ru, slug_hy, slug_ar,
                    meta_title_ru, meta_title_hy, meta_title_ar,
                    meta_description_ru, meta_description_hy, meta_description_ar,
                    meta_keywords_ru, meta_keywords_hy, meta_keywords_ar,
                    excerpt_ru, excerpt_hy, excerpt_ar, content_ru, content_hy, content_ar
             FROM blog_posts
             WHERE status = "published"
               AND (slug = ? OR slug_ru = ? OR slug_hy = ? OR slug_ar = ?)
             LIMIT 1'
        );
        $stmt->execute([$slug, $slug, $slug, $slug]);
        $row = $stmt->fetch();
        if ($row) {
            $post = ad_blog_normalize_post($row);
        }
    } catch (Throwable $e) {
        error_log('Blog single error: ' . $e->getMessage());
    }
}

if ($post) {
    try {
        $db = ad_db();
        $stmt = $db->prepare(
            'SELECT id, title, slug, meta_title, meta_description, meta_keywords, excerpt, content, cover, published_at,
                    title_ru, title_hy, title_ar, slug_ru, slug_hy, slug_ar,
                    meta_title_ru, meta_title_hy, meta_title_ar,
                    meta_description_ru, meta_description_hy, meta_description_ar,
                    meta_keywords_ru, meta_keywords_hy, meta_keywords_ar,
                    excerpt_ru, excerpt_hy, excerpt_ar, content_ru, content_hy, content_ar
             FROM blog_posts
             WHERE status = "published" AND id != ?
             ORDER BY published_at DESC, id DESC
             LIMIT 3'
        );
        $stmt->execute([(int) $post['id']]);
        $rows = $stmt->fetchAll();
        $relatedPosts = array_map('ad_blog_normalize_post', $rows);
    } catch (Throwable $e) {
        error_log('Related posts error: ' . $e->getMessage());
    }
}

if ($post) {
    $articleTitle = ad_blog_localized_field($post, 'title');
    $pageTitle = ad_blog_localized_field($post, 'meta_title') ?: $articleTitle . ' | ' . __('brand');
    $metaDescription = ad_blog_localized_field($post, 'meta_description') ?: ad_blog_localized_field($post, 'excerpt');
    $metaKeywords = ad_blog_localized_field($post, 'meta_keywords');
    $ogImage = ad_blog_localized_field($post, 'cover');
    $currentPath = 'blog-single/' . ad_blog_post_slug($post, $currentLang);
    $postSlug = ad_blog_post_slug($post, $currentLang);
    $hreflangPathByLang = [];
    foreach (ad_supported_languages() as $langCode) {
        $hreflangPathByLang[$langCode] = 'blog-single/' . ad_blog_post_slug($post, $langCode);
    }
    $schemaOrg = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $articleTitle,
        'datePublished' => $post['published_at'] ?? '',
        'image' => $ogImage !== '' ? $ogImage : null,
        'author' => ['@type' => 'Organization', 'name' => __('brand')],
        'publisher' => ['@type' => 'Organization', 'name' => __('brand')],
        'inLanguage' => $currentLang,
    ];
} else {
    $articleTitle = '';
    $pageTitle = __('page_not_found') . ' | ' . __('brand');
    $metaDescription = __('page_not_found_desc');
    $currentPath = 'blog-single/' . $slug;
}

$shareUrl = '';
if ($post) {
    $config = ad_config();
    $base = rtrim((string) ($config['site_url'] ?? ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    $shareUrl = $base . url($currentPath);
    $shareTitle = rawurlencode($articleTitle);
    $shareUrlEnc = rawurlencode($shareUrl);
}

include __DIR__ . '/includes/header.php';
?>

    <?php if ($post): ?>
        <div class="container-fluid bg-breadcrumb blog-hero blog-hero--single">
            <div class="container text-center py-5" style="max-width: 960px;">
                <ol class="breadcrumb justify-content-center text-white mb-3 wow fadeInDown" data-wow-delay="0.05s">
                    <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __('home') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= url('blog') ?>" class="text-white"><?= __('blog') ?></a></li>
                    <li class="breadcrumb-item active text-secondary" aria-current="page"><?= htmlspecialchars($articleTitle) ?></li>
                </ol>
                <h1 class="text-white display-5 mb-0 wow fadeInDown" data-wow-delay="0.15s"><?= htmlspecialchars($articleTitle) ?></h1>
            </div>
        </div>

        <section class="blog-single-page">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-9">
                        <article class="blog-article wow fadeInUp">
                            <header class="blog-article__header">
                                <?php
                                $dateLabel = ad_blog_format_date($post['published_at'] ?? '');
                                $categories = $post['categories'] ?? [];
                                ?>
                                <ul class="blog-article__meta list-unstyled d-flex flex-wrap align-items-center gap-3 mb-0">
                                    <?php if ($dateLabel !== ''): ?>
                                        <li>
                                            <time datetime="<?= htmlspecialchars(substr((string) ($post['published_at'] ?? ''), 0, 10)) ?>">
                                                <i class="fas fa-calendar-alt text-secondary me-1" aria-hidden="true"></i>
                                                <?= htmlspecialchars($dateLabel) ?>
                                            </time>
                                        </li>
                                    <?php endif; ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <li>
                                            <span class="blog-article__tag"><?= htmlspecialchars($cat['name']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </header>

                            <?php
                            $cover = ad_blog_localized_field($post, 'cover');
                            $coverAlt = trim((string) ($post['cover_alt'] ?? ''));
                            if ($coverAlt === '') {
                                $coverAlt = $articleTitle;
                            }
                            ?>
                            <?php if ($cover !== ''): ?>
                                <figure class="blog-article__figure">
                                    <img src="<?= htmlspecialchars($cover) ?>" class="blog-article__cover" alt="<?= htmlspecialchars($coverAlt) ?>" width="1200" height="630" decoding="async">
                                </figure>
                            <?php endif; ?>

                            <?php $excerpt = ad_blog_localized_field($post, 'excerpt'); ?>
                            <?php if ($excerpt !== ''): ?>
                                <p class="blog-article__lead"><?= htmlspecialchars($excerpt) ?></p>
                            <?php endif; ?>

                            <div class="blog-article__body blog-body">
                                <?= ad_blog_localized_field($post, 'content') ?>
                            </div>

                            <footer class="blog-article__footer">
                                <div class="blog-article__footer-grid">
                                    <div class="blog-article__tags">
                                        <?php if ($categories !== []): ?>
                                            <span class="blog-article__footer-label"><?= __('blog') ?></span>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($categories as $cat): ?>
                                                    <span class="blog-article__tag blog-article__tag--outline"><?= htmlspecialchars($cat['name']) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="blog-article__share">
                                        <span class="blog-article__footer-label"><?= __('share') ?></span>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="blog-share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrlEnc ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                                <i class="fab fa-facebook-f" aria-hidden="true"></i>
                                            </a>
                                            <a class="blog-share-btn" href="https://twitter.com/intent/tweet?url=<?= $shareUrlEnc ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                                                <i class="fab fa-twitter" aria-hidden="true"></i>
                                            </a>
                                            <a class="blog-share-btn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrlEnc ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                                                <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </footer>
                        </article>

                        <div class="blog-single-nav mt-4 wow fadeInUp">
                            <a href="<?= url('blog') ?>" class="blog-single-nav__back btn btn-outline-primary rounded-pill px-4 py-2">
                                <i class="fas fa-arrow-left me-2" aria-hidden="true"></i><?= __('blog') ?>
                            </a>
                        </div>

                        <?php if ($relatedPosts !== []): ?>
                            <section class="blog-related mt-5 pt-4 wow fadeInUp" aria-labelledby="related-heading">
                                <div class="blog-related__head">
                                    <h2 id="related-heading" class="blog-related__title"><?= __('related_posts') ?></h2>
                                </div>
                                <div class="row g-4">
                                    <?php foreach ($relatedPosts as $index => $related): ?>
                                        <?php
                                        $post = $related;
                                        $cardVariant = 'compact';
                                        include __DIR__ . '/includes/partials/blog-card.php';
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h1 class="text-white display-5 mb-4 wow fadeInDown"><?= __('page_not_found') ?></h1>
                <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown">
                    <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __('home') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= url('blog') ?>" class="text-white"><?= __('blog') ?></a></li>
                    <li class="breadcrumb-item active text-secondary"><?= __('page_not_found') ?></li>
                </ol>
            </div>
        </div>
        <section class="blog-single-page">
            <div class="container py-5 text-center">
                <div class="blog-empty wow fadeInUp">
                    <div class="blog-empty__icon text-warning" aria-hidden="true">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="text-muted mb-4"><?= __('page_not_found_desc') ?></p>
                    <a href="<?= url('blog') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5"><?= __('blog') ?></a>
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';