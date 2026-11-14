<?php
/**
 * Blog page - fetches posts directly from database with multilingual support
 */
require_once __DIR__ . '/backend/lib/db.php';
require_once __DIR__ . '/backend/lib/helpers.php';
require_once __DIR__ . '/backend/lib/media.php';
require_once __DIR__ . '/backend/lib/blog.php';
require_once __DIR__ . '/router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

ad_blog_ensure_schema();

$posts = [];
$categories = [];

try {
    $categories = ad_blog_categories_list();
    $db = ad_db();
    $rows = $db->query(
        'SELECT id, title, slug, meta_title, meta_description, meta_keywords, excerpt, content, cover, published_at,
                title_ru, title_hy, slug_ru, slug_hy, meta_title_ru, meta_title_hy,
                meta_description_ru, meta_description_hy, meta_keywords_ru, meta_keywords_hy,
                excerpt_ru, excerpt_hy, content_ru, content_hy
         FROM blog_posts
         WHERE status = "published"
         ORDER BY published_at DESC, id DESC
         LIMIT 50'
    )->fetchAll();
    $posts = array_map('ad_blog_normalize_post', $rows);
} catch (Throwable $e) {
    error_log('Blog page error: ' . $e->getMessage());
}

// Language-specific content getter
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
    ];
    $langMonths = $months[$GLOBALS['currentLang']] ?? $months['en'];
    return $dt->format('d') . ' ' . $langMonths[(int)$dt->format('m') - 1] . ' ' . $dt->format('Y');
}

// SEO for blog listing
$pageTitle = __("latest_articles") . ' | ' . __("brand");
$metaDescription = __("blog_meta_desc");
$currentPath = 'blog';

include __DIR__ . '/includes/header.php';
?>

    <!-- Blog Start -->
    <div class="container-fluid blog overflow-hidden py-5">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("recent_posts") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("latest_articles") ?></h1>
                <p class="mb-0"><?= __("blog_subtitle") ?></p>
            </div>

            <?php if (empty($posts)): ?>
                <div class="text-center py-5 wow fadeInUp">
                    <i class="fas fa-newspaper fa-4x text-secondary mb-3"></i>
                    <h4 class="text-secondary"><?= __("no_posts") ?></h4>
                    <p class="text-muted"><?= __("no_posts_desc") ?? 'No articles published yet. Check back soon!' ?></p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($posts as $index => $post): ?>
                        <div class="col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="<?= $index * 0.1 ?>s">
                            <article class="blog-item h-100">
                                <?php 
                                $cover = getPostField($post, 'cover');
                                $coverAlt = getPostField($post, 'cover_alt') ?: getPostField($post, 'title');
                                ?>
                                <?php if (!empty($cover)): ?>
                                    <div class="blog-img position-relative overflow-hidden">
                                        <img src="<?= htmlspecialchars($cover) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars($coverAlt) ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="blog-img position-relative overflow-hidden bg-secondary" style="height: 250px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-newspaper fa-4x text-white opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="blog-content p-4 d-flex flex-column">
                                    <div class="blog-meta mb-3">
                                        <span class="me-3"><i class="fas fa-calendar-alt text-primary me-1"></i><?= formatDate($post['published_at']) ?></span>
                                        <?php if (!empty($post['categories'])): ?>
                                            <span><i class="fas fa-folder text-primary me-1"></i><?= htmlspecialchars(implode('، ', array_column($post['categories'], 'name'))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="blog-title mb-3">
                                        <a href="<?= url('blog-single/' . ad_blog_post_slug($post, $currentLang)) ?>" class="text-secondary text-decoration-none"><?= htmlspecialchars(getPostField($post, 'title')) ?></a>
                                    </h4>
                                    <p class="blog-excerpt text-muted flex-grow-1"><?= htmlspecialchars(getPostField($post, 'excerpt')) ?></p>
                                    <a href="<?= url('blog-single/' . ad_blog_post_slug($post, $currentLang)) ?>" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mt-auto align-self-start"><?= __("read_more") ?> <i class="fas fa-arrow-left ms-2"></i></a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-5 wow fadeInUp">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link bg-light text-secondary border-0 rounded-pill px-4" href="#" tabindex="-1"><?= __("prev") ?? 'Previous' ?></a>
                        </li>
                        <li class="page-item active"><a class="page-link bg-primary border-0 rounded-pill px-4" href="#">۱</a></li>
                        <li class="page-item"><a class="page-link bg-light text-secondary border-0 rounded-pill px-4" href="#">۲</a></li>
                        <li class="page-item"><a class="page-link bg-light text-secondary border-0 rounded-pill px-4" href="#">۳</a></li>
                        <li class="page-item">
                            <a class="page-link bg-light text-secondary border-0 rounded-pill px-4" href="#"><?= __("next") ?? 'Next' ?></a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    <!-- Blog End -->

<?php
include __DIR__ . '/includes/footer.php';