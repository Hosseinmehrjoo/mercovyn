<?php
/**
 * About page - Multilingual (Mercovyn)
 */
require_once __DIR__ . '/../backend/lib/db.php';
require_once __DIR__ . '/../backend/lib/helpers.php';
require_once __DIR__ . '/../router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

$pageTitle = __('about') . ' | ' . __('brand');
$metaDescription = __('about_meta_description');
$currentPath = 'about';
$loadMercovynVideos = true;
$loadCounterUp = true;

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h3 class="text-white display-3 mb-4 wow fadeInDown" data-wow-delay="0.1s"><?= __('about_us') ?></h3>
            <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __('home') ?></a></li>
                <li class="breadcrumb-item active text-secondary"><?= __('about') ?></li>
            </ol>
        </div>
    </div>

    <div class="container-fluid overflow-hidden py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-xl-5 wow fadeInLeft" data-wow-delay="0.1s">
                    <div class="bg-light rounded">
                        <img src="<?= root_asset('img/about-2.png') ?>" class="img-fluid w-100" style="margin-bottom: -7px;" alt="<?= htmlspecialchars(__('about_img_factory_alt'), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                        <img src="<?= root_asset('img/about-3.jpg') ?>" class="img-fluid w-100 border-bottom border-5 border-primary" style="border-top-right-radius: 300px; border-top-left-radius: 300px;" alt="<?= htmlspecialchars(__('about_img_production_alt'), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                    </div>
                </div>
                <div class="col-xl-7 wow fadeInRight" data-wow-delay="0.3s">
                    <h5 class="sub-title text-primary pe-3"><?= __('why_choose_us') ?></h5>
                    <h1 class="display-5 mb-4"><?= __('about_page_heading') ?></h1>
                    <p class="lead mb-4"><?= __('about_page_lead') ?></p>
                    <p class="text-muted mb-4"><?= __('about_page_body') ?></p>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <p class="text-primary h6 mb-0"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('product_made_in') ?> — <?= __('product_made_in_country') ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-primary h6 mb-0"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('product_breaking_strength') ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-primary h6 mb-0"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('product_colors') ?></p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-primary h6 mb-0"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('visa_assistance') ?></p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <a href="<?= url('contact') ?>" class="btn btn-primary rounded-pill px-4"><?= __('contact_us') ?></a>
                        <a href="<?= url('products') ?>" class="btn btn-outline-primary rounded-pill px-4"><?= __('our_services') ?></a>
                        <a href="tel:+37491551987" class="text-secondary fw-bold text-nowrap"><?= __('phone_number') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php $videoPlacement = 'about'; include __DIR__ . '/../includes/partials/video-carousel-section.php'; ?>

<?php include __DIR__ . '/../includes/partials/about-after-video.php'; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>