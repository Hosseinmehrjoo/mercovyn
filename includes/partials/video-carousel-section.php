<?php
/**
 * Video carousel slot (home | about | home_products, …).
 *
 * @var string $videoPlacement
 * @var bool   $videoSectionShowHeading
 */
$videoPlacement = $videoPlacement ?? 'home';
if (!function_exists('ad_video_placement_valid')) {
    require_once dirname(__DIR__, 2) . '/backend/lib/videos.php';
}
if (!ad_video_placement_valid($videoPlacement)) {
    $videoPlacement = 'home';
}
$videoSectionShowHeading = $videoSectionShowHeading ?? true;
$useFactoryHeadings = in_array($videoPlacement, ['about', 'home_products'], true);
$videoKicker = $useFactoryHeadings ? __('videos_factory_kicker') : __('videos_section_kicker');
$videoTitle = $useFactoryHeadings ? __('videos_factory_title') : __('videos_section_title');
$videoSubtitle = $useFactoryHeadings ? __('videos_factory_subtitle') : __('videos_section_subtitle');
$sectionDomId = 'mercovyn-videos-' . $videoPlacement;
$sectionExtraClass = $videoPlacement === 'home_products' ? ' mercovyn-videos-section--compact' : '';
?>
<section id="<?= htmlspecialchars($sectionDomId, ENT_QUOTES, 'UTF-8') ?>"
         class="container-fluid py-5 mercovyn-videos-section<?= $sectionExtraClass ?>"
         data-video-placement="<?= htmlspecialchars($videoPlacement, ENT_QUOTES, 'UTF-8') ?>"
         hidden
         aria-hidden="true">
    <?php
    $videoFloaterA = root_asset('assets/images/uploads/services/9055030c1a700415.webp');
    $videoFloaterB = root_asset('assets/images/uploads/services/08881aee7d7b7c57.webp');
    $videoFloaters = [
        ['src' => $videoFloaterA, 'x' => '-560px', 'y' => '16%', 's' => '168px', 'r' => '-10deg', 'd' => '0.05s', 'in' => '210px', 'far' => false],
        ['src' => $videoFloaterB, 'x' => '-430px', 'y' => '58%', 's' => '112px', 'r' => '7deg', 'd' => '0.22s', 'in' => '160px', 'far' => false],
        ['src' => $videoFloaterA, 'x' => '-690px', 'y' => '46%', 's' => '86px', 'r' => '-4deg', 'd' => '0.38s', 'in' => '240px', 'far' => true],
        ['src' => $videoFloaterB, 'x' => '400px', 'y' => '14%', 's' => '132px', 'r' => '8deg', 'd' => '0.12s', 'in' => '-180px', 'far' => false],
        ['src' => $videoFloaterA, 'x' => '470px', 'y' => '54%', 's' => '188px', 'r' => '-6deg', 'd' => '0.28s', 'in' => '-220px', 'far' => false],
        ['src' => $videoFloaterB, 'x' => '680px', 'y' => '36%', 's' => '78px', 'r' => '12deg', 'd' => '0.46s', 'in' => '-250px', 'far' => true],
    ];
    ?>
    <div class="video-floaters" aria-hidden="true">
        <?php foreach ($videoFloaters as $floater): ?>
            <div class="video-floater<?= $floater['far'] ? ' video-floater--far' : '' ?>"
                 style="--x:<?= htmlspecialchars($floater['x'], ENT_QUOTES, 'UTF-8') ?>;--y:<?= htmlspecialchars($floater['y'], ENT_QUOTES, 'UTF-8') ?>;--s:<?= htmlspecialchars($floater['s'], ENT_QUOTES, 'UTF-8') ?>;--r:<?= htmlspecialchars($floater['r'], ENT_QUOTES, 'UTF-8') ?>;--d:<?= htmlspecialchars($floater['d'], ENT_QUOTES, 'UTF-8') ?>;--in:<?= htmlspecialchars($floater['in'], ENT_QUOTES, 'UTF-8') ?>">
                <img class="video-floater__img" src="<?= htmlspecialchars($floater['src'], ENT_QUOTES, 'UTF-8') ?>" alt="" width="200" height="200" decoding="async">
            </div>
        <?php endforeach; ?>
    </div>
    <div class="container py-3">
        <?php if ($videoSectionShowHeading): ?>
        <div class="section-title text-center mb-4">
            <div class="sub-style">
                <h5 class="sub-title text-primary px-3"><?= htmlspecialchars($videoKicker, ENT_QUOTES, 'UTF-8') ?></h5>
            </div>
            <h2 class="display-6 mb-2"><?= htmlspecialchars($videoTitle, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($videoSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>
        <div class="video-carousel" id="videoCarousel-<?= htmlspecialchars($videoPlacement, ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" class="video-carousel-button prev" aria-label="<?= __("video_carousel_prev") ?>">‹</button>
            <div class="video-carousel-track"></div>
            <button type="button" class="video-carousel-button next" aria-label="<?= __("video_carousel_next") ?>">›</button>
        </div>
    </div>
</section>