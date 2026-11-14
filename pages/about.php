<?php
/**
 * About page - Multilingual
 */
require_once __DIR__ . '/../backend/lib/db.php';
require_once __DIR__ . '/../backend/lib/helpers.php';
require_once __DIR__ . '/../router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

$pageTitle = __('about') . ' | ' . __('brand');
$metaDescription = __('meta_description_default');
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

    <!-- About main -->
    <div class="container-fluid overflow-hidden py-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-xl-5 wow fadeInLeft" data-wow-delay="0.1s">
                    <div class="bg-light rounded">
                        <img src="<?= root_asset('img/about-2.png') ?>" class="img-fluid w-100" style="margin-bottom: -7px;" alt="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= root_asset('img/about-3.jpg') ?>" class="img-fluid w-100 border-bottom border-5 border-primary" style="border-top-right-radius: 300px; border-top-left-radius: 300px;" alt="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-xl-7 wow fadeInRight" data-wow-delay="0.3s">
                    <h5 class="sub-title pe-3"><?= __('about_us') ?></h5>
                    <h1 class="display-5 mb-4"><?= __('brand') ?> — <?= __('tagline') ?></h1>
                    <p class="mb-4"><?= __('meta_description_default') ?></p>
                    <div class="row gy-4 align-items-center">
                        <div class="col-12 col-sm-6 d-flex align-items-center">
                            <i class="fas fa-industry fa-3x text-secondary"></i>
                            <h5 class="ms-4"><?= __('hero_stat_experience_label') ?? '20 Years Manufacturing Experience' ?></h5>
                        </div>
                        <div class="col-12 col-sm-6 d-flex align-items-center">
                            <i class="fas fa-globe fa-3x text-secondary"></i>
                            <h5 class="ms-4"><?= __('popular_countries') ?></h5>
                        </div>
                        <div class="col-4 col-md-3">
                            <div class="bg-light text-center rounded p-3">
                                <div class="mb-2">
                                    <i class="fas fa-cube fa-4x text-primary"></i>
                                </div>
                                <h1 class="display-5 fw-bold mb-2">6</h1>
                                <p class="text-muted mb-0"><?= __('popular_countries') ?></p>
                            </div>
                        </div>
                        <div class="col-8 col-md-9">
                            <div class="mb-5">
                                <p class="text-primary h6 mb-3"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('brand') ?></p>
                                <p class="text-primary h6 mb-3"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('faster_processing') ?></p>
                                <p class="text-primary h6 mb-3"><i class="fa fa-check-circle text-secondary me-2"></i> <?= __('cost_effective') ?></p>
                            </div>
                            <div class="d-flex flex-wrap">
                                <div id="phone-tada" class="d-flex align-items-center justify-content-center me-4">
                                    <a href="tel:+37491551987" class="position-relative wow tada" data-wow-delay=".9s">
                                        <i class="fa fa-phone-alt text-primary fa-3x"></i>
                                        <div class="position-absolute" style="top: 0; left: 25px;">
                                            <span><i class="fa fa-comment-dots text-secondary"></i></span>
                                        </div>
                                    </a>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <span class="text-primary"><?= __('contact') ?></span>
                                    <span class="text-secondary fw-bold fs-5" style="letter-spacing: 2px;"><?= __('phone_number') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php $videoPlacement = 'about'; include __DIR__ . '/../includes/partials/video-carousel-section.php'; ?>

<?php include __DIR__ . '/../includes/partials/about-after-video.php'; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>