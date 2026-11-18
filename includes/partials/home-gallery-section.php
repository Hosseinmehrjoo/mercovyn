<?php
/**
 * Homepage image gallery (below final CTA). Expects $galleryImages list or loads from DB.
 */
require_once dirname(__DIR__, 2) . '/backend/lib/helpers.php';
$galleryInitialVisible = 8;
if (!isset($galleryImages)) {
    require_once dirname(__DIR__, 2) . '/backend/lib/gallery.php';
    try {
        $galleryImages = ad_gallery_list_public();
    } catch (Throwable $e) {
        $galleryImages = [];
    }
}
if ($galleryImages === []) {
    return;
}
$total = count($galleryImages);
$hasMore = $total > $galleryInitialVisible;
?>
<section class="container-fluid home-gallery-section py-5" style="background: #f8f9fa;" id="home-gallery">
    <div class="container py-4">
        <div class="section-title text-center mb-4 wow fadeInUp" data-wow-delay="0.1s">
            <div class="sub-style">
                <h5 class="sub-title text-primary px-3"><?= __("home_gallery_subtitle") ?></h5>
            </div>
            <h2 class="display-6 mb-2"><?= __("home_gallery_title") ?></h2>
        </div>
        <div class="row g-3 home-gallery-grid" data-initial="<?= (int) $galleryInitialVisible ?>">
            <?php foreach ($galleryImages as $index => $item): ?>
                <?php
                $src = ad_public_upload_url((string) ($item['image_url'] ?? ''));
                $altRaw = ad_gallery_localized_title($item, $currentLang ?? getCurrentLanguage());
                if ($altRaw === '' || preg_match('/^\d+$/u', $altRaw)) {
                    $altRaw = __('home_gallery_image_alt');
                }
                $alt = htmlspecialchars($altRaw, ENT_QUOTES, 'UTF-8');
                $isExtra = $index >= $galleryInitialVisible;
                ?>
                <div class="col-6 col-md-4 col-lg-3 home-gallery-item<?= $isExtra ? ' home-gallery-item--extra d-none' : '' ?> wow fadeInUp"
                     data-wow-delay="<?= min(0.5, $index * 0.05) ?>s">
                    <button type="button" class="home-gallery-thumb w-100 border-0 p-0 rounded overflow-hidden shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#homeGalleryModal"
                            data-gallery-src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"
                            data-gallery-alt="<?= $alt ?>"
                            aria-label="<?= $alt ?>">
                        <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>" class="img-fluid w-100 home-gallery-thumb__img"
                             alt="<?= $alt ?>" loading="lazy" decoding="async">
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($hasMore): ?>
            <div class="text-center mt-4">
                <button type="button" class="btn btn-primary rounded-pill px-5 py-2 home-gallery-toggle" id="homeGalleryToggle"
                        data-more-label="<?= htmlspecialchars(__('gallery_show_more'), ENT_QUOTES, 'UTF-8') ?>"
                        data-less-label="<?= htmlspecialchars(__('gallery_show_less'), ENT_QUOTES, 'UTF-8') ?>"
                        data-more-extra="<?= (int) ($total - $galleryInitialVisible) ?>"
                        aria-expanded="false" aria-controls="home-gallery">
                    <?= __("gallery_show_more") ?>
                    <span class="home-gallery-toggle__extra ms-1 text-white-50">(<?= (int) ($total - $galleryInitialVisible) ?>+)</span>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade" id="homeGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2 p-md-3 text-center">
                <img src="" alt="" class="img-fluid rounded home-gallery-modal__img" id="homeGalleryModalImg" style="max-height:85vh;width:auto;">
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var toggle = document.getElementById('homeGalleryToggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            document.querySelectorAll('.home-gallery-item--extra').forEach(function (el) {
                el.classList.toggle('d-none', expanded);
            });
            this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            var extra = this.getAttribute('data-more-extra') || '0';
            this.innerHTML = expanded
                ? this.getAttribute('data-more-label') + ' <span class="home-gallery-toggle__extra ms-1 text-white-50">(' + extra + '+)</span>'
                : this.getAttribute('data-less-label');
            if (!expanded) {
                var extra = document.querySelector('.home-gallery-item--extra');
                if (extra) {
                    extra.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        });
    }
    var modal = document.getElementById('homeGalleryModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (ev) {
            var btn = ev.relatedTarget;
            if (!btn || !btn.getAttribute) return;
            var img = document.getElementById('homeGalleryModalImg');
            if (!img) return;
            img.src = btn.getAttribute('data-gallery-src') || '';
            img.alt = btn.getAttribute('data-gallery-alt') || '';
        });
    }
})();
</script>