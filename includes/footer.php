<?php
/**
 * Shared Footer Component
 */

require_once __DIR__ . '/../router.php';
?>
    <!-- Newsletter Start -->
    <div class="container-fluid newsletter py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h3 class="display-5 mb-4"><?= __("newsletter") ?></h3>
                    <p class="mb-0"><?= __("newsletter_desc") ?? 'Subscribe to our newsletter for latest product updates and export news' ?></p>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                    <form class="input-group" action="#" method="post">
                        <input type="email" class="form-control p-3 border-0 rounded-pill" placeholder="<?= __("email_placeholder_footer") ?>" required name="email">
                        <button class="btn btn-primary border-secondary rounded-pill px-5" type="submit"><?= __("subscribe") ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Newsletter End -->

    <!-- Footer Start -->
    <div class="container-fluid footer py-5">
        <div class="container py-5">
            <a href="<?= url('') ?>" class="footer-top-logo wow fadeInUp" data-wow-delay="0.05s">
                <img src="/img/mercovyn-logo.webp" class="site-logo-footer" alt="<?= htmlspecialchars(__('brand'), ENT_QUOTES, 'UTF-8') ?>" width="300" height="300" loading="lazy" decoding="async">
            </a>
            <div class="row g-5">
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <p class="text-white mb-4"><?= __("footer_desc") ?></p>
                    <p class="text-white mb-4"><?= __("company_name_am") ?></p>
                    <div class="d-flex">
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href="#"><i class="fab fa-twitter fw-normal"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href="#"><i class="fab fa-facebook-f fw-normal"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href="#"><i class="fab fa-linkedin-in fw-normal"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle" href="#"><i class="fab fa-instagram fw-normal"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <h4 class="text-white mb-4"><?= __("quick_links") ?></h4>
                    <div class="d-flex flex-column justify-content-start">
                        <a class="text-white mb-2" href="<?= url('') ?>"><i class="fas fa-angle-left me-2"></i><?= __("home") ?></a>
                        <a class="text-white mb-2" href="<?= url('about') ?>"><i class="fas fa-angle-left me-2"></i><?= __("about") ?></a>
                        <a class="text-white mb-2" href="<?= url('products') ?>"><i class="fas fa-angle-left me-2"></i><?= __("services") ?></a>
                        <a class="text-white mb-2" href="<?= url('blog') ?>"><i class="fas fa-angle-left me-2"></i><?= __("blog") ?></a>
                        <a class="text-white" href="<?= url('contact') ?>"><i class="fas fa-angle-left me-2"></i><?= __("contact") ?></a>
                    </div>
                </div>
                <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.5s">
                    <h4 class="text-white mb-4"><?= __("contact_info") ?></h4>
                    <p class="text-white mb-3"><i class="fas fa-map-marker-alt me-2"></i><?= __("address") ?></p>
                    <p class="text-white mb-3"><i class="fas fa-phone-alt me-2"></i><a href="tel:+37491551987" class="text-white"><?= __("phone_number") ?></a></p>
                    <p class="text-white mb-3"><i class="fas fa-envelope me-2"></i><a href="mailto:mercovyn@gmail.com" class="text-white"><?= __("email_address") ?></a></p>
                    <p class="text-white mb-0"><i class="fab fa-whatsapp me-2"></i><a href="<?= __("whatsapp_link") ?>" class="text-white" target="_blank">WhatsApp</a></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Copyright Start -->
    <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-white"><a href="<?= url('') ?>"><i class="fas fa-copyright me-2"></i><?= __("brand") ?></a>, <?= __("all_rights_reserved") ?></span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="text-white"><?= __("designed_by") ?> <a class="text-primary" href="https://hosseinmehrjoo.ir" target="_blank" rel="noopener noreferrer"><?= __("designer_name") ?></a></span>
                </div>
            </div>
        </div>
    </div>
    <!-- Copyright End -->

    <?php include __DIR__ . '/floating-contact.php'; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="<?= root_asset('lib/owlcarousel/owl.carousel.min.js') ?>"></script>
    <script src="<?= root_asset('lib/wow/wow.min.js') ?>"></script>
    <script src="<?= root_asset('lib/easing/easing.min.js') ?>"></script>
    <script src="<?= root_asset('lib/waypoints/waypoints.min.js') ?>"></script>
    <script src="<?= root_asset('lib/counterup/counterup.min.js') ?>"></script>

    <!-- Template Javascript -->
    <script src="<?= htmlspecialchars(asset_url('js/main.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(asset_url('js/floating-contact.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>

</html>