<?php
/**
 * Homepage — custom twine colors (image + CTA).
 */
$customColorsImg = root_asset('assets/images/other/custom-colors-twine.webp');
$contactUrl = url('contact');
?>
<section class="container-fluid custom-colors-section py-5" aria-labelledby="custom-colors-heading">
    <div class="container py-4 py-lg-5">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-7 wow fadeInLeft" data-wow-delay="0.1s">
                <figure class="custom-colors-section__figure mb-0">
                    <img
                        src="<?= htmlspecialchars($customColorsImg, ENT_QUOTES, 'UTF-8') ?>"
                        width="1024"
                        height="384"
                        alt="<?= htmlspecialchars(__('custom_colors_img_alt'), ENT_QUOTES, 'UTF-8') ?>"
                        class="custom-colors-section__img"
                        loading="lazy"
                        decoding="async"
                    >
                </figure>
            </div>
            <div class="col-lg-5 wow fadeInRight" data-wow-delay="0.2s">
                <div class="section-title text-start mb-4">
                    <div class="sub-style">
                        <h5 class="sub-title text-primary px-3 mb-3"><?= __('custom_colors_eyebrow') ?></h5>
                    </div>
                    <h2 id="custom-colors-heading" class="display-6 fw-bold text-dark mb-3"><?= __('custom_colors_title') ?></h2>
                    <p class="text-muted lead mb-4 mb-lg-5"><?= __('custom_colors_desc') ?></p>
                </div>
                <ul class="custom-colors-section__palette list-unstyled d-flex flex-wrap align-items-center gap-2 mb-4" aria-hidden="true">
                    <li><span class="custom-colors-section__swatch" style="--swatch:#1a1a1a"></span></li>
                    <li><span class="custom-colors-section__swatch" style="--swatch:#e02454"></span></li>
                    <li><span class="custom-colors-section__swatch" style="--swatch:#1e6fd9"></span></li>
                    <li><span class="custom-colors-section__swatch" style="--swatch:#f5c542"></span></li>
                    <li><span class="custom-colors-section__swatch" style="--swatch:#2d8f4e"></span></li>
                    <li><span class="custom-colors-section__swatch" style="--swatch:#7b3fd4"></span></li>
                    <li class="custom-colors-section__palette-more text-muted small ms-1"><?= __('custom_colors_palette_more') ?></li>
                </ul>
                <p class="custom-colors-section__note text-muted small mb-4"><?= __('custom_colors_note') ?></p>
                <a href="<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary border-secondary rounded-pill py-3 px-5">
                    <?= __('custom_colors_cta') ?> <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>