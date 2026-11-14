<?php
/**
 * Video carousel slot (home | about).
 *
 * @var string $videoPlacement
 */
$videoPlacement = $videoPlacement ?? 'home';
if (!in_array($videoPlacement, ['home', 'about'], true)) {
    $videoPlacement = 'home';
}
$sectionDomId = 'mercovyn-videos-' . $videoPlacement;
?>
<section id="<?= htmlspecialchars($sectionDomId, ENT_QUOTES, 'UTF-8') ?>"
         class="container-fluid py-5 mercovyn-videos-section"
         data-video-placement="<?= htmlspecialchars($videoPlacement, ENT_QUOTES, 'UTF-8') ?>"
         hidden
         aria-hidden="true">
    <div class="container py-3">
        <div class="section-title text-center mb-4">
            <div class="sub-style">
                <h5 class="sub-title text-primary px-3"><?= __("videos_section_kicker") ?></h5>
            </div>
            <h2 class="display-6 mb-2"><?= __("videos_section_title") ?></h2>
            <p class="text-muted mb-0"><?= __("videos_section_subtitle") ?></p>
        </div>
        <div class="video-carousel" id="videoCarousel-<?= htmlspecialchars($videoPlacement, ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" class="video-carousel-button prev" aria-label="<?= __("video_carousel_prev") ?>">‹</button>
            <div class="video-carousel-track"></div>
            <button type="button" class="video-carousel-button next" aria-label="<?= __("video_carousel_next") ?>">›</button>
        </div>
    </div>
</section>