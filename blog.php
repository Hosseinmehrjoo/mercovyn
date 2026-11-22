<?php
/**
 * Blog page - fetches posts directly from database with multilingual support
 */
require_once __DIR__ . '/backend/lib/db.php';
require_once __DIR__ . '/backend/lib/helpers.php';
require_once __DIR__ . '/backend/lib/media.php';
require_once __DIR__ . '/backend/lib/blog.php';
require_once __DIR__ . '/backend/lib/seo.php';
require_once __DIR__ . '/router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

ad_blog_ensure_schema();

$posts = [];

try {
    $db = ad_db();
    $rows = $db->query(
        'SELECT id, title, slug, meta_title, meta_description, meta_keywords, excerpt, content, cover, published_at,
                title_ru, title_hy, title_ar, title_ka, slug_ru, slug_hy, slug_ar, slug_ka,
                meta_title_ru, meta_title_hy, meta_title_ar, meta_title_ka,
                meta_description_ru, meta_description_hy, meta_description_ar, meta_description_ka,
                meta_keywords_ru, meta_keywords_hy, meta_keywords_ar, meta_keywords_ka,
                excerpt_ru, excerpt_hy, excerpt_ar, excerpt_ka, content_ru, content_hy, content_ar, content_ka
         FROM blog_posts
         WHERE status = "published"
         ORDER BY published_at DESC, id DESC
         LIMIT 50'
    )->fetchAll();
    $posts = array_map('ad_blog_normalize_post', $rows);
} catch (Throwable $e) {
    error_log('Blog page error: ' . $e->getMessage());
}

$pageTitle = __('latest_articles') . ' | ' . __('brand');
$metaDescription = __('blog_meta_desc');
$currentPath = 'blog';

$schemaLdNodes = [
    ad_seo_schema_breadcrumb([
        ['name' => __('home'), 'path' => ''],
        ['name' => __('blog')],
    ], $currentPath),
];

include __DIR__ . '/includes/header.php';
?>

    <div class="container-fluid bg-breadcrumb blog-hero">
        <div class="container text-center py-5" style="max-width: 900px;">
            <p class="blog-hero__kicker text-secondary text-uppercase fw-semibold mb-2 wow fadeInDown" data-wow-delay="0.05s">
                <?= __('recent_posts') ?>
            </p>
            <h1 class="text-white display-4 mb-3 wow fadeInDown" data-wow-delay="0.1s"><?= __('latest_articles') ?></h1>
            <p class="text-white opacity-75 mb-4 wow fadeInDown" data-wow-delay="0.15s"><?= __('blog_subtitle') ?></p>
            <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown" data-wow-delay="0.25s">
                <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __('home') ?></a></li>
                <li class="breadcrumb-item active text-secondary"><?= __('blog') ?></li>
            </ol>
        </div>
    </div>

    <section class="blog-archive" aria-labelledby="blog-archive-heading">
        <div class="container py-5">
            <h2 id="blog-archive-heading" class="visually-hidden"><?= __('latest_articles') ?></h2>

            <?php if ($posts === []): ?>
                <div class="blog-empty text-center py-5 wow fadeInUp">
                    <div class="blog-empty__icon" aria-hidden="true">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <h3 class="h4 text-primary mb-2"><?= __('no_posts') ?></h3>
                    <p class="text-muted mb-0"><?= __('no_posts_desc') ?></p>
                </div>
            <?php else: ?>
                <div class="row g-4 blog-archive__grid">
                    <?php foreach ($posts as $index => $post): ?>
                        <?php
                        $index = $index;
                        include __DIR__ . '/includes/partials/blog-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php
include __DIR__ . '/includes/footer.php';