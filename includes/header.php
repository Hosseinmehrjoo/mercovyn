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
$pageDir = $currentLang === 'ar' ? 'rtl' : 'ltr';
$ogLocales = ['en' => 'en_US', 'ru' => 'ru_RU', 'hy' => 'hy_AM', 'ar' => 'ar_SA', 'ka' => 'ka_GE'];
$ogLocale = $ogLocales[$currentLang] ?? 'en_US';
$loadMercovynVideos = $loadMercovynVideos ?? false;
$overlayNavOnHero = !empty($overlayNavOnHero);

// Default SEO values
$pageTitle = $pageTitle ?? __("brand") . ' - ' . __("tagline");
$metaDescription = $metaDescription ?? __("meta_description_default");
$metaKeywords = $metaKeywords ?? __("meta_keywords_default");
$ogImage = $ogImage ?? '';
$currentPath = $currentPath ?? '';
$logoUrl = '/img/mercovyn-logo.webp';
if ($ogImage === '') {
    $ogImage = getCanonicalSiteOrigin() . $logoUrl;
}

$hreflangPathByLang = $hreflangPathByLang ?? [];
$hreflangUrls = getHreflangUrls($currentPath, $hreflangPathByLang);
$canonicalUrl = $hreflangUrls[$currentLang] ?? buildCanonicalUrl(trim($langPrefix, '/'), $currentPath);
$hreflangTags = '';
foreach ($hreflangUrls as $lang => $url) {
    $rel = $lang === 'x-default' ? 'alternate' : 'alternate';
    $hreflang = $lang;
    $hreflangTags .= '    <link rel="alternate" hreflang="' . htmlspecialchars($hreflang, ENT_QUOTES, 'UTF-8')
        . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "\" />\n";
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8') ?>" dir="<?= htmlspecialchars($pageDir, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="<?= $logoUrl ?>" type="image/webp">
    <link rel="apple-touch-icon" href="<?= $logoUrl ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="<?= htmlspecialchars($metaKeywords) ?>" name="keywords">
    <meta content="<?= htmlspecialchars($metaDescription) ?>" name="description">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>" />
    
    <!-- Hreflang Tags for SEO -->
<?= $hreflangTags ?>
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($ogImage): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>
    <meta property="og:locale" content="<?= htmlspecialchars($ogLocale, ENT_QUOTES, 'UTF-8') ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <?php endif; ?>

    <?php
    require_once __DIR__ . '/../backend/lib/seo.php';
    $schemaLdNodes = $schemaLdNodes ?? [];
    if (!empty($schemaOrg)) {
        $schemaLdNodes[] = $schemaOrg;
        unset($schemaOrg);
    }
    $schemaJsonLd = ad_seo_json_ld_document(array_merge([ad_seo_schema_organization()], $schemaLdNodes));
    if ($schemaJsonLd !== []): ?>
    <script type="application/ld+json"><?= json_encode($schemaJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" as="image" href="<?= $logoUrl ?>" type="image/webp" fetchpriority="high">
    <?php if (!empty($preloadHeroImage)): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars(root_asset($preloadHeroImage), ENT_QUOTES, 'UTF-8') ?>" type="image/webp">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://use.fontawesome.com">

    <link rel="preload" href="<?= root_asset('css/bootstrap.min.css') ?>" as="style">
    <link rel="preload" href="<?= htmlspecialchars(asset_url('css/style.css'), ENT_QUOTES, 'UTF-8') ?>" as="style">

    <!-- Critical CSS -->
    <link href="<?= root_asset('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/style.css'), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Non-critical CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.5.1/css/all.css" media="print" onload="this.media='all'">
    <?php if (!empty($loadOwlCarousel)): ?>
    <link href="<?= root_asset('lib/owlcarousel/assets/owl.carousel.min.css') ?>" rel="stylesheet" media="print" onload="this.media='all'">
    <?php endif; ?>
    <?php if (!empty($loadMercovynVideos)): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/mercovyn-videos.css'), ENT_QUOTES, 'UTF-8') ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/mercovyn-videos.css'), ENT_QUOTES, 'UTF-8') ?>"></noscript>
    <?php endif; ?>
    <?php include __DIR__ . '/clarity.php'; ?>
</head>

<body>

<?php include __DIR__ . '/page-skeleton.php'; ?>

    <!-- Topbar Start -->
    <div class="container-fluid bg-primary px-5 d-none d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-5 text-center text-lg-start mb-lg-0">
                <div class="d-flex">
                    <a href="mailto:<?= htmlspecialchars(__('email_address'), ENT_QUOTES, 'UTF-8') ?>" class="text-muted me-4"><i class="fas fa-envelope text-secondary me-2"></i><?= htmlspecialchars(__('email_address'), ENT_QUOTES, 'UTF-8') ?></a>
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
                    <a href="<?= url('contact') ?>" class="text-muted ms-2"><?= __("contact") ?></a>
                    <span class="text-muted mx-2 d-none d-xl-inline" aria-hidden="true">|</span>
                    <?php include __DIR__ . '/lang-switcher.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <?php
    $heroBgVideo = null;
    if ($overlayNavOnHero) {
        require_once __DIR__ . '/../backend/lib/videos.php';
        try {
            $heroList = ad_videos_list_public($currentLang, 'hero', 1);
            $heroBgVideo = $heroList[0] ?? null;
        } catch (Throwable $e) {
            $heroBgVideo = null;
        }
    }
    ?>
    <?php if ($overlayNavOnHero): ?>
    <div class="home-hero-shell<?= $heroBgVideo ? ' home-hero-shell--video' : '' ?>">
        <?php if ($heroBgVideo): ?>
        <video class="home-hero-shell__video" autoplay muted loop playsinline
               <?php if (!empty($heroBgVideo['poster_url'])): ?>poster="<?= htmlspecialchars((string) $heroBgVideo['poster_url'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
               aria-hidden="true">
            <source src="<?= htmlspecialchars((string) $heroBgVideo['video_url'], ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
        </video>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0<?= $overlayNavOnHero ? ' nav-bar--hero-overlay' : '' ?>">
        <div class="navbar-mobile-lang d-lg-none border-bottom bg-white px-3 py-2">
            <div class="d-flex justify-content-center justify-content-md-end">
                <?php $langSwitcherVariant = 'mobile-header'; include __DIR__ . '/lang-switcher.php'; unset($langSwitcherVariant); ?>
            </div>
        </div>
        <nav class="navbar navbar-expand-lg navbar-light bg-white px-4 px-lg-5 py-3 py-lg-0">
            <a href="<?= url('') ?>" class="navbar-brand p-0 d-inline-flex align-items-center" aria-label="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= $logoUrl ?>" class="site-logo" alt="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>" width="120" height="120" decoding="async" fetchpriority="high">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-lg-auto py-0 text-center text-lg-start">
                    <a href="<?= url('') ?>" class="nav-item nav-link"><?= __("home") ?></a>
                    <a href="<?= url('about') ?>" class="nav-item nav-link"><?= __("about") ?></a>
                    <a href="<?= url('products') ?>" class="nav-item nav-link"><?= __("services") ?></a>
                    <a href="<?= url('blog') ?>" class="nav-item nav-link"><?= __("blog") ?></a>
                    <a href="<?= url('contact') ?>" class="nav-item nav-link"><?= __("contact") ?></a>
                </div>
                <div class="d-lg-none navbar-mobile-tools w-100 pt-3 mt-2 border-top">
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