<?php
/**
 * Single product page
 */
require_once __DIR__ . '/../backend/lib/db.php';
require_once __DIR__ . '/../backend/lib/helpers.php';
require_once __DIR__ . '/../backend/lib/services.php';
require_once __DIR__ . '/../router.php';

$currentLang = getCurrentLanguage();
$slug = trim((string) ($_GET['slug'] ?? ''));
$product = $slug !== '' ? ad_service_find_by_slug($slug) : null;

$others = [];
if ($product !== null) {
    foreach (ad_service_list_active() as $row) {
        if ((int) $row['id'] === (int) $product['id']) {
            continue;
        }
        $others[] = $row;
        if (count($others) >= 3) {
            break;
        }
    }
}

if ($product) {
    $title = ad_service_localized_field($product, 'title');
    $pageTitle = $title . ' | ' . __("brand");
    $metaDescription = ad_service_localized_field($product, 'summary') ?: __("meta_description_default");
    $currentPath = 'product/' . ad_service_localized_slug($product);
    $ogImage = trim((string) ($product['cover'] ?? ''));
} else {
    $pageTitle = __("page_not_found") . ' | ' . __("brand");
    $metaDescription = __("page_not_found_desc");
    $currentPath = 'product/' . $slug;
    $ogImage = '';
}

include __DIR__ . '/../includes/header.php';

$contactUrl = url('contact');
$whatsapp = __('whatsapp_link');
?>

    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h3 class="text-white display-3 mb-4 wow fadeInDown" data-wow-delay="0.1s"><?= $product ? htmlspecialchars($title) : __("page_not_found") ?></h3>
            <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __("home") ?></a></li>
                <li class="breadcrumb-item"><a href="<?= url('products') ?>" class="text-white"><?= __("services") ?></a></li>
                <li class="breadcrumb-item active text-secondary"><?= $product ? htmlspecialchars($title) : __("page_not_found") ?></li>
            </ol>
        </div>
    </div>

    <section class="container-fluid product-single py-5">
        <div class="container py-5">
            <?php if (!$product): ?>
                <div class="text-center py-5 wow fadeInUp">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                    <h4 class="text-secondary"><?= __("page_not_found") ?></h4>
                    <p class="text-muted"><?= __("page_not_found_desc") ?></p>
                    <a href="<?= url('products') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5"><?= __("back_to_products") ?></a>
                </div>
            <?php else: ?>
                <?php
                $summary = ad_service_localized_field($product, 'summary');
                $content = ad_service_localized_field($product, 'content');
                $cover = trim((string) ($product['cover'] ?? ''));
                ?>
                <div class="row g-5 align-items-start">
                    <div class="col-lg-6 wow fadeInLeft">
                        <div class="product-single__media rounded-4 overflow-hidden shadow-sm">
                            <?php if ($cover !== ''): ?>
                                <img src="<?= htmlspecialchars($cover) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars($title) ?>">
                            <?php else: ?>
                                <div class="product-card__media-placeholder product-card__media-placeholder--lg">
                                    <i class="fas fa-seedling fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInRight">
                        <span class="badge bg-secondary text-white mb-3 px-3 py-2 rounded-pill"><?= __("product_no_price") ?></span>
                        <h1 class="display-5 mb-4"><?= htmlspecialchars($title) ?></h1>
                        <?php if ($summary !== ''): ?>
                            <p class="lead text-muted mb-4"><?= nl2br(htmlspecialchars($summary)) ?></p>
                        <?php endif; ?>
                        <div class="product-single__inquiry p-4 rounded-4 mb-4">
                            <h5 class="text-primary mb-2"><?= __("product_inquiry_title") ?></h5>
                            <p class="mb-0 text-muted"><?= __("product_inquiry_desc") ?></p>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?= htmlspecialchars($contactUrl) ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5">
                                <i class="fas fa-envelope me-2"></i><?= __("send_message") ?>
                            </a>
                            <a href="tel:+37491551987" class="btn btn-outline-primary rounded-pill py-3 px-4">
                                <i class="fas fa-phone-alt me-2"></i><?= __("phone_number") ?>
                            </a>
                            <a href="<?= htmlspecialchars($whatsapp) ?>" class="btn btn-outline-success rounded-pill py-3 px-4" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-whatsapp me-2"></i><?= __("whatsapp") ?>
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($content !== ''): ?>
                    <div class="row mt-5 wow fadeInUp">
                        <div class="col-12">
                            <div class="product-single__content bg-light rounded-4 p-4 p-lg-5">
                                <h2 class="h4 text-primary mb-4"><?= __("product_details") ?></h2>
                                <div class="product-single__body">
                                    <?= nl2br(htmlspecialchars($content)) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($others !== []): ?>
                    <div class="mt-5 pt-5 border-top">
                        <h3 class="display-6 mb-4 text-center"><?= __("related_products") ?></h3>
                        <div class="row g-4">
                            <?php foreach ($others as $index => $row): ?>
                                <?php $product = $row; include __DIR__ . '/../includes/partials/product-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

<?php
include __DIR__ . '/../includes/footer.php';
