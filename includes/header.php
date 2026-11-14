<?php
/**
 * Shared Header Component
 * Includes topbar, navbar, and language switcher
 * 
 * Variables expected:
 * - $pageTitle (optional)
 * - $metaDescription (optional)
 * - $metaKeywords (optional)
 * - $ogImage (optional)
 * - $currentPath (optional) - for hreflang URLs
 */

require_once __DIR__ . '/../router.php';

$langInfo = getLanguageInfo();
$currentLang = $langInfo['current'];
$langPrefix = $langInfo['prefix'];

// Default SEO values
$pageTitle = $pageTitle ?? __("brand") . ' - ' . __("tagline");
$metaDescription = $metaDescription ?? __("meta_description_default");
$metaKeywords = $metaKeywords ?? __("meta_keywords_default");
$ogImage = $ogImage ?? '';
$currentPath = $currentPath ?? '';
$logoUrl = '/img/mercovyn-logo.webp';
if ($ogImage === '') {
    $ogImage = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mercovyn.com') . $logoUrl;
}

$hreflangPathByLang = $hreflangPathByLang ?? [];
$hreflangUrls = getHreflangUrls($currentPath, $hreflangPathByLang);
$hreflangTags = '';
foreach ($hreflangUrls as $lang => $url) {
    $rel = $lang === 'x-default' ? 'alternate' : 'alternate';
    $hreflang = $lang;
    $hreflangTags .= "    <link rel=\"$rel\" hreflang=\"$hreflang\" href=\"$url\" />\n";
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="ltr">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="<?= $logoUrl ?>" type="image/webp">
    <link rel="apple-touch-icon" href="<?= $logoUrl ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="<?= htmlspecialchars($metaKeywords) ?>" name="keywords">
    <meta content="<?= htmlspecialchars($metaDescription) ?>" name="description">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= $hreflangUrls[$currentLang] ?? 'https://' . $_SERVER['HTTP_HOST'] . $langPrefix . '/' ?>" />
    
    <!-- Hreflang Tags for SEO -->
<?= $hreflangTags ?>
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $hreflangUrls[$currentLang] ?? 'https://' . $_SERVER['HTTP_HOST'] . $langPrefix . '/' ?>">
    <?php if ($ogImage): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>
    <meta property="og:locale" content="<?= $currentLang === 'en' ? 'en_US' : ($currentLang === 'ru' ? 'ru_RU' : 'hy_AM') ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>

    <?php
    $schemaOrg = $schemaOrg ?? [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => __('brand'),
        'url' => $hreflangUrls['en'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'mercovyn.com') . '/'),
        'logo' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mercovyn.com') . $logoUrl,
        'email' => 'mercovyn@gmail.com',
        'telephone' => '+37491551987',
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Yerevan',
            'addressCountry' => 'AM',
        ],
    ];
    if (!empty($schemaOrg)): ?>
    <script type="application/ld+json"><?= json_encode($schemaOrg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" as="image" href="<?= $logoUrl ?>" type="image/webp" fetchpriority="high">
    <?php if (!empty($preloadHeroImage)): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars(root_asset($preloadHeroImage), ENT_QUOTES, 'UTF-8') ?>" type="image/webp">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://use.fontawesome.com">

    <!-- Critical CSS (render-blocking) -->
    <link href="<?= root_asset('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css'), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Non-critical CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.5.1/css/all.css" media="print" onload="this.media='all'">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" media="print" onload="this.media='all'">
    <?php if (!empty($loadOwlCarousel)): ?>
    <link href="<?= root_asset('lib/owlcarousel/assets/owl.carousel.min.css') ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <?php endif; ?>
    <link href="<?= root_asset('lib/animate/animate.min.css') ?>" rel="stylesheet" media="print" onload="this.media='all'">
</head>

<body>

    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-secondary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only"><?= __("loading") ?></span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Topbar Start -->
    <div class="container-fluid bg-primary px-5 d-none d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-5 text-center text-lg-start mb-lg-0">
                <div class="d-flex">
                    <a href="mailto:mercovyn@gmail.com" class="text-muted me-4"><i class="fas fa-envelope text-secondary me-2"></i>mercovyn@gmail.com</a>
                    <a href="tel:+37491551987" class="text-muted me-0"><i class="fas fa-phone-alt text-secondary me-2"></i>+374 91 551 987</a>
                </div>
            </div>
            <div class="col-lg-3 row-cols-1 text-center mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a class="btn btn-sm btn-outline-light btn-square rounded-circle me-2" href=""><i class="fab fa-twitter fw-normal text-secondary"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-square rounded-circle me-2" href=""><i class="fab fa-facebook-f fw-normal text-secondary"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-square rounded-circle me-2" href=""><i class="fab fa-linkedin-in fw-normal text-secondary"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-square rounded-circle me-2" href=""><i class="fab fa-instagram fw-normal text-secondary"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-square rounded-circle" href=""><i class="fab fa-youtube fw-normal text-secondary"></i></a>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a href="#" class="text-muted me-2"><?= __("help") ?></a><small> / </small>
                    <a href="#" class="text-muted mx-2"><?= __("support") ?></a><small> / </small>
                    <a href="<?= url('contact') ?>" class="text-muted ms-2"><?= __("contact") ?></a>
                    <span class="text-muted mx-2 d-none d-xl-inline" aria-hidden="true">|</span>
                    <?php include __DIR__ . '/lang-switcher.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <nav class="navbar navbar-expand-lg navbar-light bg-white px-4 px-lg-5 py-3 py-lg-0">
            <a href="<?= url('') ?>" class="navbar-brand p-0 d-inline-flex align-items-center" aria-label="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= $logoUrl ?>" class="site-logo" alt="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>" decoding="async" fetchpriority="high">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-lg-auto py-0 text-center text-lg-start">
                    <a href="<?= url('') ?>" class="nav-item nav-link"><?= __("home") ?></a>
                    <a href="<?= url('about') ?>" class="nav-item nav-link"><?= __("about") ?></a>
                    <a href="<?= url('products') ?>" class="nav-item nav-link"><?= __("services") ?></a>
                    <a href="<?= url('contact') ?>" class="nav-item nav-link"><?= __("contact") ?></a>
                </div>
                <div class="d-lg-none navbar-mobile-tools w-100 pt-3 mt-2 border-top">
                    <p class="small text-muted text-center mb-2"><?= __("language") ?></p>
                    <div class="d-flex justify-content-center mb-3">
                        <?php $langSwitcherVariant = 'navbar'; include __DIR__ . '/lang-switcher.php'; unset($langSwitcherVariant); ?>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center px-1">
                        <button type="button" class="btn btn-primary border-secondary flex-sm-fill" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search me-2"></i><?= __("search") ?>
                        </button>
                        <a href="<?= url('contact') ?>" class="btn btn-primary border-secondary rounded-pill flex-sm-fill text-center"><?= __("get_quote") ?></a>
                    </div>
                </div>
                <div class="d-none d-lg-flex align-items-center flex-shrink-0 ms-lg-2">
                    <button type="button" class="btn btn-primary btn-md-square border-secondary me-3" data-bs-toggle="modal" data-bs-target="#searchModal" aria-label="<?= htmlspecialchars(__('search'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-search"></i></button>
                    <a href="<?= url('contact') ?>" class="btn btn-primary border-secondary rounded-pill py-2 px-4"><?= __("get_quote") ?></a>
                </div>
            </div>
        </nav>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Modal Search Start -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h4 class="modal-title text-secondary mb-0" id="exampleModalLabel"><?= __("search") ?></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div class="input-group w-75 mx-auto d-flex">
                        <input type="search" class="form-control p-3" placeholder="<?= __("search") ?>" aria-describedby="search-icon-1">
                        <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Search End -->