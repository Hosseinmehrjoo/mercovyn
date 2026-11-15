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

$slug = $_GET['slug'] ?? '';
$post = null;
$relatedPosts = [];

function getPostField(array $post, string $field): string
{
    global $currentLang;
    $suffix = $currentLang === 'en' ? '' : '_' . $currentLang;
    $localizedField = $field . $suffix;
    $value = $post[$localizedField] ?? null;
    // If localized field is empty, fall back to base field
    if ($value === '' || $value === null) {
        return $post[$field] ?? '';
    }
    return $value;
}

function formatDate($dateStr) {
    if (empty($dateStr)) return '';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
    if (!$dt) return $dateStr;
    $months = [
        'en' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        'ru' => ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
        'hy' => ['Հուն','Փետ','Մար','Ապր','Մայ','Հուն','Հուլ','Օգս','Սեպ','Հոկ','Նոյ','Դեկ'],
        'ar' => ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
    ];
    $langMonths = $months[$GLOBALS['currentLang']] ?? $months['en'];
    return $dt->format('d') . ' ' . $langMonths[(int)$dt->format('m') - 1] . ' ' . $dt->format('Y');
}

if ($slug) {
    try {
        $db = ad_db();
$stmt = $db->prepare(
            'SELECT id, title, slug, meta_title, meta_description, meta_keywords, excerpt, content, cover, published_at,
                    title_ru, title_hy, slug_ru, slug_hy, meta_title_ru, meta_title_hy,
                    meta_description_ru, meta_description_hy, meta_keywords_ru, meta_keywords_hy,
                    excerpt_ru, excerpt_hy, content_ru, content_hy
             FROM blog_posts
             WHERE status = "published"
               AND (slug = ? OR slug_ru = ? OR slug_hy = ?)
             LIMIT 1'
        );
        $stmt->execute([$slug, $slug, $slug]);
        $row = $stmt->fetch();
        if ($row) {
            $post = ad_blog_normalize_post($row);
        }
    } catch (Throwable $e) {
        error_log('Blog single error: ' . $e->getMessage());
    }
}

// Fetch related posts (latest 3 excluding current)
if ($post) {
    try {
        $db = ad_db();
        $stmt = $db->prepare(
            'SELECT id, title, slug, meta_title, meta_description, meta_keywords, excerpt, content, cover, published_at,
                    title_ru, title_hy, slug_ru, slug_hy, meta_title_ru, meta_title_hy, 
                    meta_description_ru, meta_description_hy, meta_keywords_ru, meta_keywords_hy,
                    excerpt_ru, excerpt_hy, content_ru, content_hy
             FROM blog_posts
             WHERE status = "published" AND slug != ?
             ORDER BY published_at DESC, id DESC
             LIMIT 3'
        );
        $stmt->execute([$slug]);
        $rows = $stmt->fetchAll();
        $relatedPosts = array_map('ad_blog_normalize_post', $rows);
    } catch (Throwable $e) {
        error_log('Related posts error: ' . $e->getMessage());
    }
}

// SEO for single post
if ($post) {
    $pageTitle = getPostField($post, 'meta_title') ?: getPostField($post, 'title') . ' | ' . __("brand");
    $metaDescription = getPostField($post, 'meta_description') ?: getPostField($post, 'excerpt');
    $metaKeywords = getPostField($post, 'meta_keywords');
    $ogImage = getPostField($post, 'cover');
    $currentPath = 'blog-single/' . ad_blog_post_slug($post, $currentLang);
    $postSlug = ad_blog_post_slug($post, $currentLang);
    $hreflangPathByLang = [
        'en' => 'blog-single/' . $postSlug,
        'ru' => 'blog-single/' . $postSlug,
        'hy' => 'blog-single/' . $postSlug,
        'ar' => 'blog-single/' . $postSlug,
    ];
    $schemaOrg = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => getPostField($post, 'title'),
        'datePublished' => $post['published_at'] ?? '',
        'image' => getPostField($post, 'cover') ?: null,
        'author' => ['@type' => 'Organization', 'name' => __('brand')],
        'publisher' => ['@type' => 'Organization', 'name' => __('brand')],
        'inLanguage' => $currentLang,
    ];
} else {
    $pageTitle = __("page_not_found") . ' | ' . __("brand");
    $metaDescription = __("page_not_found_desc");
    $currentPath = 'blog-single/' . $slug;
}

include __DIR__ . '/includes/header.php';
?>

    <!-- Blog Single Start -->
    <div class="container-fluid blog-single overflow-hidden py-5">
        <div class="container py-5">
            <?php if (!$post): ?>
                <div class="text-center py-5 wow fadeInUp">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                    <h4 class="text-secondary"><?= __("page_not_found") ?></h4>
                    <p class="text-muted"><?= __("page_not_found_desc") ?></p>
                    <a href="<?= url('blog') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5 mt-3"><?= __("back_home") ?? 'Back to Blog' ?></a>
                </div>
            <?php else: ?>
                <article class="wow fadeInUp">
                    <?php 
                    $cover = getPostField($post, 'cover');
                    $coverAlt = getPostField($post, 'cover_alt') ?: getPostField($post, 'title');
                    ?>
                    <?php if (!empty($cover)): ?>
                        <div class="blog-img mb-4 rounded overflow-hidden">
                            <img src="<?= htmlspecialchars($cover) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars($coverAlt) ?>">
                        </div>
                    <?php endif; ?>

                    <header class="mb-4">
                        <div class="blog-meta mb-3">
                            <span class="me-3"><i class="fas fa-calendar-alt text-primary me-1"></i><?= formatDate($post['published_at']) ?></span>
                            <?php if (!empty($post['categories'])): ?>
                                <span><i class="fas fa-folder text-primary me-1"></i><?= htmlspecialchars(implode('، ', array_column($post['categories'], 'name'))) ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="display-5 mb-4"><?= htmlspecialchars(getPostField($post, 'title')) ?></h1>
                    </header>

                    <div class="blog-content">
                        <?php 
                        $excerpt = getPostField($post, 'excerpt');
                        if (!empty($excerpt)): ?>
                            <div class="lead text-muted border-start border-primary ps-4 mb-5">
                                <?= htmlspecialchars($excerpt) ?>
                            </div>
                        <?php endif; ?>

                        <div class="blog-body">
                            <?= getPostField($post, 'content') ?>
                        </div>
                    </div>

                    <footer class="blog-footer mt-5 pt-4 border-top">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($post['categories'])): ?>
                                        <?php foreach ($post['categories'] as $cat): ?>
                                            <a href="<?= url('blog?category=' . $cat['slug']) ?>" class="btn btn-sm btn-outline-primary rounded-pill"><?= htmlspecialchars($cat['name']) ?></a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="d-flex flex-wrap justify-content-md-end gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill" data-share="facebook"><i class="fab fa-facebook-f me-1"></i> <?= __("share") ?></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill" data-share="twitter"><i class="fab fa-twitter me-1"></i> <?= __("tweet") ?></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill" data-share="linkedin"><i class="fab fa-linkedin-in me-1"></i> <?= __("linkedin") ?></a>
                                </div>
                            </div>
                        </div>
                    </footer>
                </article>

                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                    <section class="related-posts mt-5 wow fadeInUp">
                        <h3 class="display-6 mb-4"><?= __("related_posts") ?></h3>
                        <div class="row g-4">
                            <?php foreach ($relatedPosts as $index => $related): ?>
                                <div class="col-lg-4 wow fadeInUp" data-wow-delay="<?= $index * 0.1 ?>s">
                                    <article class="blog-item h-100">
                                        <?php 
                                        $rCover = getPostField($related, 'cover');
                                        $rCoverAlt = getPostField($related, 'cover_alt') ?: getPostField($related, 'title');
                                        ?>
                                        <?php if (!empty($rCover)): ?>
                                            <div class="blog-img position-relative overflow-hidden">
                                                <img src="<?= htmlspecialchars($rCover) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars($rCoverAlt) ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="blog-img position-relative overflow-hidden bg-secondary" style="height: 200px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-newspaper fa-3x text-white opacity-50"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="blog-content p-3 d-flex flex-column">
                                            <h5 class="blog-title mb-2">
                                                <a href="<?= url('blog-single/' . ad_blog_post_slug($related, $currentLang)) ?>" class="text-secondary text-decoration-none"><?= htmlspecialchars(getPostField($related, 'title')) ?></a>
                                            </h5>
                                            <a href="<?= url('blog-single/' . ad_blog_post_slug($related, $currentLang)) ?>" class="btn btn-sm btn-primary border-secondary rounded-pill mt-auto align-self-start"><?= __("read_more") ?> <i class="fas fa-arrow-left ms-1"></i></a>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- Blog Single End -->

<?php
include __DIR__ . '/includes/footer.php';