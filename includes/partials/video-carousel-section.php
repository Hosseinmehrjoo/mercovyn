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