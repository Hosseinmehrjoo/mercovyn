<?php
/**
 * Homepage - Multilingual
 */
require_once __DIR__ . '/../backend/lib/db.php';
require_once __DIR__ . '/../backend/lib/helpers.php';
require_once __DIR__ . '/../backend/lib/media.php';
require_once __DIR__ . '/../backend/lib/services.php';
require_once __DIR__ . '/../router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

try {
    require_once __DIR__ . '/../backend/lib/services.php';
    ad_services_ensure_schema();
    $services = ad_service_list_active(6);
} catch (Throwable $e) {
    $services = [];
    error_log('Homepage error: ' . $e->getMessage());
}

$team = [];
$portfolio = [];

function getField(array $row, string $field, string $lang): string
{
    $suffix = $lang === 'en' ? '' : '_' . $lang;
    $localized = $field . $suffix;
    $value = $row[$localized] ?? null;
    if ($value === '' || $value === null) {
        return $row[$field] ?? '';
    }
    return $value;
}

$pageTitle = __('home_meta_title');
$metaDescription = __("meta_description_default");
$currentPath = '';
$preloadHeroImage = 'assets/images/other/hero-bg.webp';
$loadOwlCarousel = true;
$loadCounterUp = false;
$loadMercovynVideos = true;

include __DIR__ . '/../includes/header.php';
?>

    <!-- Header Start -->
    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h3 class="text-white display-3 mb-4 wow fadeInDown" data-wow-delay="0.1s"><?= __("home_hero_title") ?></h3>
            <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __("home") ?></a></li>
                <li class="breadcrumb-item active text-secondary"><?= __("home") ?></li>
            </ol>
        </div>
    </div>
    <!-- Header End -->

    <!-- Hero Section -->
    <section class="container-fluid hero-section py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.1s">
                    <h1 class="display-4 mb-4"><?= __("hero_title") ?></h1>
                    <p class="lead mb-2"><?= __("hero_subtitle") ?></p>
                    <p class="text-muted mb-4"><?= __("global_export_tagline") ?></p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= url('contact') ?>" class="btn btn-primary btn-lg rounded-pill px-4"><?= __("get_quote") ?></a>
                        <a href="<?= url('products') ?>" class="btn btn-outline-primary btn-lg rounded-pill px-4"><?= __("our_services") ?></a>
                    </div>
                </div>
                <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.3s">
                    <div class="hero-stats row g-3 text-center">
                        <div class="col-6">
                            <div class="bg-white p-4 rounded shadow-sm">
                                <h2 class="text-primary mb-1 fs-3">6–6.5</h2>
                                <p class="mb-0 text-muted small"><?= __("hero_stat_spool_label") ?></p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-4 rounded shadow-sm">
                                <h2 class="text-primary mb-1">PP</h2>
                                <p class="mb-0 text-muted small"><?= __("hero_stat_pp_label") ?></p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-4 rounded shadow-sm">
                                <h2 class="text-primary mb-1 counter" data-target="800">800</h2>
                                <p class="mb-0 text-muted"><?= __("team_members") ?></p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-4 rounded shadow-sm">
                                <h2 class="text-primary mb-1 counter" data-target="70">70</h2>
                                <p class="mb-0 text-muted"><?= __("awards_won") ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    $videoPlacement = 'home_products';
    include __DIR__ . '/../includes/partials/video-carousel-section.php';
    ?>

    <!-- Products Section -->
    <section class="container-fluid services-section products-section py-4">
        <div class="container py-4">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("service_categories") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("enabling_immigration") ?></h1>
                <p class="mb-0"><?= __("services_subtitle") ?></p>
            </div>
            <?php if ($services === []): ?>
                <p class="text-center text-muted wow fadeInUp"><?= __("no_products_desc") ?></p>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $index => $product): ?>
                        <?php include __DIR__ . '/../includes/partials/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4 wow fadeInUp" data-wow-delay="0.3s">
                <a href="<?= url('products') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5"><?= __("view_all") ?> <i class="fas fa-arrow-left ms-2"></i></a>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../includes/partials/custom-colors-section.php'; ?>

    <?php include __DIR__ . '/../includes/partials/home-gallery-section.php'; ?>

    <!-- Features/Advantages Section -->
    <section class="container-fluid features-section py-5" style="background: #f8f9fa;">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("why_choose_us") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("our_features") ?></h1>
                <p class="mb-0"><?= __("features_subtitle") ?></p>
            </div>
            <div class="row g-4 justify-content-center text-center">
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="feature-item text-center p-4 h-100">
                        <div class="feature-icon p-3 mb-4">
                            <i class="fas fa-industry fa-4x text-primary"></i>
                        </div>
                        <div class="feature-content d-flex flex-column">
                            <h5 class="mb-3"><?= __("cost_effective") ?></h5>
                            <p class="mb-3"><?= __("cost_effective_desc") ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="feature-item text-center p-4 h-100">
                        <div class="feature-icon p-3 mb-4">
                            <i class="fas fa-globe fa-4x text-primary"></i>
                        </div>
                        <div class="feature-content d-flex flex-column">
                            <h5 class="mb-3"><?= __("visa_assistance") ?></h5>
                            <p class="mb-3"><?= __("visa_assistance_desc") ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="feature-item text-center p-4 h-100">
                        <div class="feature-icon p-3 mb-4">
                            <i class="fas fa-palette fa-4x text-primary"></i>
                        </div>
                        <div class="feature-content d-flex flex-column">
                            <h5 class="mb-3"><?= __("faster_processing") ?></h5>
                            <p class="mb-3"><?= __("faster_processing_desc") ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.7s">
                    <div class="feature-item text-center p-4 h-100">
                        <div class="feature-icon p-3 mb-4">
                            <i class="fas fa-truck fa-4x text-primary"></i>
                        </div>
                        <div class="feature-content d-flex flex-column">
                            <h5 class="mb-3"><?= __("direct_interviews") ?></h5>
                            <p class="mb-3"><?= __("direct_interviews_desc") ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php $videoPlacement = 'home'; include __DIR__ . '/../includes/partials/video-carousel-section.php'; ?>

    <!-- Team Section -->
    <?php if (!empty($team)): ?>
    <section class="container-fluid team-section py-5">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("meet_team") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("our_team") ?></h1>
                <p class="mb-0"><?= __("team_subtitle") ?></p>
            </div>
            <div class="row g-4">
                <?php foreach ($team as $index => $member): ?>
                    <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="<?= $index * 0.1 ?>s">
                        <div class="team-item text-center h-100">
                            <?php 
                            $photo = getField($member, 'photo', $currentLang);
                            ?>
                            <div class="team-img position-relative overflow-hidden">
                                <?php if (!empty($photo)): ?>
                                    <img src="<?= htmlspecialchars($photo) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars(getField($member, 'name', $currentLang)) ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center" style="height: 250px;">
                                        <i class="fas fa-user fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="team-social position-absolute bottom-0 start-0 end-0 p-3 bg-dark bg-opacity-75">
                                    <a href="" class="btn btn-sm btn-square btn-outline-light rounded-circle me-2"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="" class="btn btn-sm btn-square btn-outline-light rounded-circle me-2"><i class="fab fa-twitter"></i></a>
                                    <a href="" class="btn btn-sm btn-square btn-outline-light rounded-circle"><i class="fab fa-facebook-f"></i></a>
                                </div>
                            </div>
                            <div class="team-content p-4">
                                <h5 class="mb-1"><?= htmlspecialchars(getField($member, 'name', $currentLang)) ?></h5>
                                <p class="text-primary mb-3"><?= htmlspecialchars(getField($member, 'role', $currentLang)) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Gallery/Portfolio Section -->
    <?php if (!empty($portfolio)): ?>
    <section class="container-fluid portfolio-section py-5" style="background: #f8f9fa;">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("case_studies") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("portfolio") ?></h1>
                <p class="mb-0"><?= __("portfolio_subtitle") ?></p>
            </div>
            <div class="row g-4">
                <?php foreach ($portfolio as $index => $item): ?>
                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?= $index * 0.1 ?>s">
                        <div class="portfolio-item h-100">
                            <?php 
                            $cover = getField($item, 'cover', $currentLang);
                            ?>
                            <div class="portfolio-img position-relative overflow-hidden">
                                <?php if (!empty($cover)): ?>
                                    <img src="<?= htmlspecialchars($cover) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars(getField($item, 'title', $currentLang)) ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center" style="height: 250px;">
                                        <i class="fas fa-image fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="portfolio-overlay position-absolute top-0 start-0 end-0 bottom-0 bg-primary bg-opacity-75 d-flex align-items-center justify-content-center opacity-0 transition">
                                    <a href="<?= url('portfolio') ?>" class="btn btn-light rounded-pill"><?= __("view_all") ?></a>
                                </div>
                            </div>
                            <div class="portfolio-content p-4">
                                <h5 class="mb-2"><?= htmlspecialchars(getField($item, 'title', $currentLang)) ?></h5>
                                <p class="text-muted small mb-2"><?= htmlspecialchars(getField($item, 'client_name', $currentLang)) ?></p>
                                <p class="text-muted small"><?= htmlspecialchars(getField($item, 'description', $currentLang)) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4 wow fadeInUp" data-wow-delay="0.3s">
                <a href="<?= url('training') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5"><?= __("view_all") ?> <i class="fas fa-arrow-left ms-2"></i></a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Product Specifications Section -->
    <section class="container-fluid cta-section py-5" style="background: linear-gradient(135deg, #1d2327 0%, #2c3338 100%);">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-8 wow fadeInUp" data-wow-delay="0.1s">
                    <h2 class="text-white display-5 mb-3"><?= __("cta_title") ?></h2>
                    <p class="text-white-50 mb-0"><?= __("cta_subtitle") ?></p>
                </div>
                <div class="col-lg-4 text-lg-end wow fadeInUp" data-wow-delay="0.3s">
                    <a href="<?= url('contact') ?>" class="btn btn-primary btn-lg border-secondary rounded-pill py-3 px-5 w-100"><?= __("get_quote") ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Specs Table -->
    <section class="container-fluid py-5" style="background: #f8f9fa;">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3">Specifications</h5>
                </div>
                <h1 class="display-5 mb-4">Built to a Standard, Not a Guess</h1>
                <p class="mb-0">Everything buyers need at a glance: material, spool size, strength, coverage and resistance.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-cube fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_material") ?></h5>
                        <p class="text-muted small">UV-stabilized, weather & rot resistant</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-weight fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_spool_weight") ?></h5>
                        <p class="text-muted small">Per spool</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-dumbbell fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_breaking_strength") ?></h5>
                        <p class="text-muted small">Knot & tensile</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-seedling fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_coverage") ?></h5>
                        <p class="text-muted small">Bales per pair of spools*</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-palette fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_colors") ?></h5>
                        <p class="text-muted small">Made to order</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_resistance") ?></h5>
                        <p class="text-muted small">No taste or odor — rodent-neutral</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.7s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-flag fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_made_in") ?></h5>
                        <p class="text-muted small"><?= __("product_made_in_country") ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="0.8s">
                    <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                        <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                        <h5><?= __("product_imported_to") ?></h5>
                        <p class="text-muted small"><?= __("export_armenia") ?></p>
                    </div>
                </div>
            </div>
            <p class="text-muted small text-center mt-3">* Approximate — depends on bale size and density.</p>
        </div>
    </section>

    <!-- Export Timeline Section -->
    <section class="container-fluid py-5">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3">Export Timeline</h5>
                </div>
                <h1 class="display-5 mb-4">Already Tying Bales in Six Countries</h1>
                <p class="mb-0">Twelve years of steady export relationships, one country at a time — and Armenia is the newest thread.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="bg-white p-4 rounded shadow-sm h-100 text-center">
                        <h2 class="text-primary mb-1">2014</h2>
                        <h5 class="mb-2"><?= __("export_georgia") ?></h5>
                        <p class="text-muted small">Long-standing supply relationships across the Caucasus market.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="bg-white p-4 rounded shadow-sm h-100 text-center">
                        <h2 class="text-primary mb-1">2016</h2>
                        <h5 class="mb-2"><?= __("export_turkmenistan") ?></h5>
                        <p class="text-muted small">Trusted supply for agricultural buyers and distribution partners.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="bg-white p-4 rounded shadow-sm h-100 text-center">
                        <h2 class="text-primary mb-1">2018</h2>
                        <h5 class="mb-2"><?= __("export_iraq") ?></h5>
                        <p class="text-muted small">Serving practical, high-demand market needs with durable twine.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="bg-white p-4 rounded shadow-sm h-100 text-center">
                        <h2 class="text-primary mb-1">2021</h2>
                        <h5 class="mb-2"><?= __("export_uzbekistan") ?></h5>
                        <p class="text-muted small">Expansion into Central Asian buyers needing consistent quality.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="bg-white p-4 rounded shadow-sm h-100 text-center">
                        <h2 class="text-primary mb-1">2023</h2>
                        <h5 class="mb-2"><?= __("export_tajikistan") ?></h5>
                        <p class="text-muted small">Continued regional growth through stable export relationships.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="bg-primary p-4 rounded shadow-sm h-100 text-center text-white">
                        <h2 class="text-white mb-1">2024</h2>
                        <h5 class="mb-2"><?= __("export_armenia") ?></h5>
                        <p class="text-white-50 small">A new local base in Yerevan with the same proven production standards.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="container-fluid testimonial-section py-5">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __("what_clients_say") ?></h5>
                </div>
                <h1 class="display-5 mb-4"><?= __("testimonials") ?></h1>
            </div>
            <div class="owl-carousel testimonial-carousel owl-theme" data-autoplay="true" data-loop="true" data-items="1" data-dots="true" data-nav="false">
                <div class="testimonial-item text-center p-4">
                    <div class="testimonial-img mb-4">
                        <img src="<?= root_asset('img/testimonial-1.jpg') ?>" class="img-fluid rounded-circle mx-auto" style="width: 80px; height: 80px;" alt="Client">
                    </div>
                    <p class="fs-5 text-muted mb-4">"Mercovyn's baler twine is the most consistent we've used. Runs clean through our presses, no snags, no breaks. Reliable supply every season."</p>
                    <h5 class="mb-1">Armen Petrosyan</h5>
                    <p class="text-primary mb-0">Farm Owner - Armavir Region</p>
                </div>
                <div class="testimonial-item text-center p-4">
                    <div class="testimonial-img mb-4">
                        <img src="<?= root_asset('img/testimonial-2.jpg') ?>" class="img-fluid rounded-circle mx-auto" style="width: 80px; height: 80px;" alt="Client">
                    </div>
                    <p class="fs-5 text-muted mb-4">"We've been distributing Mercovyn twine for 3 years. Quality is steady, packaging is export-ready, and the team communicates clearly. A solid partner."</p>
                    <h5 class="mb-1">Dmitri Volkov</h5>
                    <p class="text-primary mb-0">Distributor - Georgia</p>
                </div>
                <div class="testimonial-item text-center p-4">
                    <div class="testimonial-img mb-4">
                        <img src="<?= root_asset('img/testimonial-3.jpg') ?>" class="img-fluid rounded-circle mx-auto" style="width: 80px; height: 80px;" alt="Client">
                    </div>
                    <p class="fs-5 text-muted mb-4">"Custom color order came out exactly as requested. The rose twine is highly visible in the field — our operators love it. Will order again."</p>
                    <h5 class="mb-1">Bahrom Nazarov</h5>
                    <p class="text-primary mb-0">Agricultural Manager - Uzbekistan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="container-fluid py-5" style="background: linear-gradient(135deg, #1d2327 0%, #2c3338 100%);">
        <div class="container py-5 text-center">
            <div class="wow fadeInUp" data-wow-delay="0.1s">
                <h2 class="text-white display-5 mb-3"><?= __("cta_title") ?></h2>
                <p class="text-white-50 mb-4"><?= __("cta_subtitle") ?></p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="tel:+37491551987" class="btn btn-primary btn-lg border-secondary rounded-pill py-3 px-5"><i class="fas fa-phone-alt me-2"></i><?= __("phone_number") ?></a>
                    <a href="<?= __("whatsapp_link") ?>" target="_blank" class="btn btn-outline-light btn-lg rounded-pill py-3 px-5"><i class="fab fa-whatsapp me-2"></i>WhatsApp</a>
                    <a href="mailto:mercovyn@gmail.com" class="btn btn-outline-light btn-lg rounded-pill py-3 px-5"><i class="fas fa-envelope me-2"></i>Email</a>
                </div>
            </div>
        </div>
    </section>

<?php
include __DIR__ . '/../includes/footer.php';