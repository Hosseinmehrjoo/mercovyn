<?php
/**
 * Products archive (services from admin panel)
 */
require_once __DIR__ . '/../backend/lib/db.php';
require_once __DIR__ . '/../backend/lib/helpers.php';
require_once __DIR__ . '/../backend/lib/services.php';
require_once __DIR__ . '/../router.php';

$currentLang = getCurrentLanguage();
$products = ad_service_list_active();

$pageTitle = __("our_services") . ' | ' . __("brand");
$metaDescription = __("services_subtitle");
$currentPath = 'products';
$loadMercovynVideos = true;

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h3 class="text-white display-3 mb-4 wow fadeInDown" data-wow-delay="0.1s"><?= __("our_services") ?></h3>
            <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __("home") ?></a></li>
                <li class="breadcrumb-item active text-secondary"><?= __("services") ?></li>
            </ol>
        </div>
    </div>

    <?php
    $videoPlacement = 'home_products';
    include __DIR__ . '/../includes/partials/video-carousel-section.php';
    ?>

    <section class="container-fluid products-archive products-section py-4">
        <div class="container py-4">
            <div class="section-title text-center mb-5 wow fadeInUp">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("service_categories") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("enabling_immigration") ?></h1>
                <p class="mb-0"><?= __("services_subtitle") ?></p>
                <p class="mb-0 text-muted small mt-2"><?= __("global_export_tagline") ?></p>
            </div>

            <?php if ($products === []): ?>
                <div class="text-center py-5 wow fadeInUp">
                    <i class="fas fa-box-open fa-4x text-secondary mb-3"></i>
                    <h4 class="text-secondary"><?= __("no_products") ?></h4>
                    <p class="text-muted"><?= __("no_products_desc") ?></p>
                    <a href="<?= url('contact') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5 mt-2"><?= __("contact_us") ?></a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $index => $product): ?>
                        <?php include __DIR__ . '/../includes/partials/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php
include __DIR__ . '/../includes/footer.php';
