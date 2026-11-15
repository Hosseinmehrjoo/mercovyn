<?php
/**
 * Product card — expects $product row from services table, optional $index, $cardDelay.
 */
if (empty($product) || !is_array($product)) {
    return;
}
$index = $index ?? 0;
$cardDelay = $cardDelay ?? ($index * 0.08);
$title = ad_service_localized_field($product, 'title');
$summary = ad_service_localized_field($product, 'summary');
$cover = trim((string) ($product['cover'] ?? ''));
if ($cover !== '' && function_exists('ad_public_upload_url')) {
    $cover = ad_public_upload_url($cover);
}
$detailUrl = ad_service_public_url($product);
$contactUrl = url('contact');
?>
<div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?= (float) $cardDelay ?>s">
    <article class="product-card h-100">
        <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="product-card__media" aria-hidden="true" tabindex="-1">
            <?php if ($cover !== ''): ?>
                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
            <?php else: ?>
                <div class="product-card__media-placeholder">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                </div>
            <?php endif; ?>
            <span class="product-card__badge"><?= __("product_no_price") ?></span>
        </a>
        <div class="product-card__body">
            <h3 class="product-card__title">
                <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($title) ?></a>
            </h3>
            <?php if ($summary !== ''): ?>
                <p class="product-card__summary"><?= htmlspecialchars($summary) ?></p>
            <?php endif; ?>
            <div class="product-card__actions">
                <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary border-secondary rounded-pill px-4 py-2">
                    <?= __("product_view_details") ?> <i class="fas fa-arrow-right ms-2"></i>
                </a>
                <a href="<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-pill px-4 py-2">
                    <?= __("product_contact_cta") ?>
                </a>
            </div>
        </div>
    </article>
</div>
