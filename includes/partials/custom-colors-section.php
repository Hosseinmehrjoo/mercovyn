<?php
/**
 * Homepage — custom twine colors (title + inset image + content below).
 */
$customColorsImg = root_asset('assets/images/other/custom-colors-twine.webp');
$contactUrl = url('contact');
?>
<section class="custom-colors-section" aria-labelledby="custom-colors-heading">
    <div class="container custom-colors-section__head pt-4 pt-lg-5 pb-3 pb-lg-4">
        <div class="section-title text-center mb-0 wow fadeInUp" data-wow-delay="0.05s">
            <div class="sub-style">
                <h5 class="sub-title text-primary px-3 mb-3"><?= __('custom_colors_eyebrow') ?></h5>
            </div>
            <h2 id="custom-colors-heading" class="display-5 mb-0"><?= __('custom_colors_title') ?></h2>
        </div>
    </div>

    <div class="custom-colors-section__hero wow fadeIn" data-wow-delay="0.1s">
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

    <div class="container custom-colors-section__body py-4 py-lg-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9 text-center wow fadeInUp" data-wow-delay="0.15s">
                <p class="text-muted lead mb-4"><?= __('custom_colors_desc') ?></p>
                <ul class="custom-colors-section__palette list-unstyled d-flex flex-wrap align-items-center justify-content-center gap-2 mb-4" aria-hidden="true">
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