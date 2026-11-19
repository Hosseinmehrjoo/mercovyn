<?php
/**
 * Contact page - Multilingual
 */
require_once __DIR__ . '/../backend/lib/db.php';
require_once __DIR__ . '/../backend/lib/helpers.php';
require_once __DIR__ . '/../backend/lib/media.php';
require_once __DIR__ . '/../router.php';

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

$pageTitle = __("contact") . ' | ' . __("brand");
$metaDescription = __("meta_description_default");
$currentPath = 'contact';

include __DIR__ . '/../includes/header.php';
?>

    <!-- Header Start -->
    <div class="container-fluid bg-breadcrumb">
        <div class="container text-center py-5" style="max-width: 900px;">
            <h3 class="text-white display-3 mb-4 wow fadeInDown" data-wow-delay="0.1s"><?= __("contact") ?></h3>
            <ol class="breadcrumb justify-content-center text-white mb-0 wow fadeInDown" data-wow-delay="0.3s">
                <li class="breadcrumb-item"><a href="<?= url('') ?>" class="text-white"><?= __("home") ?></a></li>
                <li class="breadcrumb-item active text-secondary"><?= __("contact") ?></li>
            </ol>
        </div>
    </div>
    <!-- Header End -->

    <!-- Contact Start -->
    <div class="container-fluid contact overflow-hidden py-5">
        <div class="container py-5">
            <div class="row g-5 mb-5">
                <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.1s">
                    <div class="sub-style">
                        <h5 class="sub-title text-primary pe-3"><?= __("quick_contact") ?? 'Quick Contact' ?></h5>
                    </div>
                    <h1 class="display-5 mb-4"><?= __("lets_talk") ?? "Let's Talk About Your Order" ?></h1>
                    <p class="mb-5"><?= __("contact_subtitle") ?? 'Call, email, or message on WhatsApp and we can discuss color, quantity, packaging and delivery options.' ?></p>

                    <div class="d-flex border-bottom mb-4 pb-4">
                        <i class="fas fa-map-marked-alt fa-4x text-primary bg-light p-3 rounded"></i>
                        <div class="ps-3">
                            <h5><?= __("location") ?></h5>
                            <p><?= __("address") ?></p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-6">
                            <div class="d-flex">
                                <i class="fas fa-phone-alt fa-3x text-primary"></i>
                                <div class="ps-3">
                                    <h5 class="mb-3"><?= __("phone") ?></h5>
                                    <div class="mb-3">
                                        <h6 class="mb-0"><?= __("call_us") ?></h6>
                                        <a href="tel:+37491551987" class="fs-5 text-primary"><?= __("phone_number") ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="d-flex">
                                <i class="fas fa-envelope fa-3x text-primary"></i>
                                <div class="ps-3">
                                    <h5 class="mb-3"><?= __("email") ?></h5>
                                    <div class="mb-3">
                                        <h6 class="mb-0"><?= __("write_us") ?></h6>
                                        <a href="mailto:<?= htmlspecialchars(__('email_address'), ENT_QUOTES, 'UTF-8') ?>" class="fs-5 text-primary"><?= __("email_address") ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center pt-3">
                        <div class="me-4">
                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 90px; height: 90px; border-radius: 10px;"><i class="fab fa-whatsapp fa-3x text-success"></i></div>
                        </div>
                        <div class="d-flex">
                            <a class="btn btn-success border-0 me-2 p-0" href="<?= __("whatsapp_link") ?>" target="_blank"><?= __("whatsapp") ?> <i class="fas fa-chevron-circle-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.3">
                    <div class="sub-style">
                        <h5 class="sub-title text-primary pe-3"><?= __("send_message") ?></h5>
                    </div>
                    <h2 class="display-5 mb-4"><?= __("get_quote_question") ?? 'Get a Quote or Ask a Question' ?></h2>
                    <p class="mb-3"><?= __("contact_form_desc") ?? 'Fill out the form below and we will get back to you shortly.' ?></p>
                    <?php
                    $productPrefill = strtolower(trim((string) ($_GET['product'] ?? '')));
                    if ($productPrefill === 'rose') {
                        $productPrefill = 'red';
                    }
                    $productOptions = [
                        'red' => __('product_red'),
                        'black' => __('product_black'),
                        'blue' => __('product_blue'),
                        'custom' => __('product_custom'),
                    ];
                    ?>
                    <form id="contactForm" novalidate>
                        <div class="row g-4">
                            <div class="col-lg-12 col-xl-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" placeholder="<?= __("name") ?>" name="name" required>
                                    <label for="name"><?= __("name") ?></label>
                                </div>
                            </div>
                            <div class="col-lg-12 col-xl-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" placeholder="<?= __("email_placeholder") ?>" name="email" required>
                                    <label for="email"><?= __("email") ?></label>
                                </div>
                            </div>
                            <div class="col-lg-12 col-xl-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="phone" placeholder="<?= __("phone_placeholder") ?>" name="phone">
                                    <label for="phone"><?= __("phone") ?></label>
                                </div>
                            </div>
                            <div class="col-lg-12 col-xl-6">
                                <div class="form-floating">
                                    <select class="form-control" id="product" name="product">
                                        <option value=""><?= __("select_product") ?></option>
                                        <?php foreach ($productOptions as $productValue => $productLabel): ?>
                                            <option value="<?= htmlspecialchars($productValue, ENT_QUOTES, 'UTF-8') ?>"<?= $productPrefill === $productValue ? ' selected' : '' ?>><?= htmlspecialchars($productLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="product"><?= __("product_interest") ?></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="subject" placeholder="<?= __("subject") ?>" name="subject">
                                    <label for="subject"><?= __("subject") ?></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="<?= __("message_placeholder") ?>" id="message" name="message" style="height: 160px" required></textarea>
                                    <label for="message"><?= __("message") ?></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100 py-3" type="submit" id="submitBtn"><?= __("send_message") ?></button>
                            </div>
                        </div>
                    </form>
                    <div id="formMessage" class="mt-3" style="display: none;"></div>
                </div>
            </div>

            <div class="office pt-5">
                <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="sub-style">
                        <h5 class="sub-title text-primary px-3"><?= __("company_details") ?></h5>
                    </div>
                    <h2 class="display-5 mb-4"><?= __("company_name") ?></h2>
                    <p class="mb-0"><?= __("company_name_am") ?> — <?= __("company_tagline") ?? 'Manufacturer and importer of baler twine in Armenia' ?></p>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.1s">
                        <div class="office-item p-4 text-center">
                            <div class="office-img mb-4">
                                <i class="fas fa-building fa-4x text-primary"></i>
                            </div>
                            <div class="office-content d-flex flex-column">
                                <h4 class="mb-2"><?= __("company") ?></h4>
                                <p class="mb-2"><?= __("company_name") ?></p>
                                <p class="mb-0"><?= __("company_name_am") ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.3s">
                        <div class="office-item p-4 text-center">
                            <div class="office-img mb-4">
                                <i class="fas fa-map-marker-alt fa-4x text-primary"></i>
                            </div>
                            <div class="office-content d-flex flex-column">
                                <h4 class="mb-2"><?= __("address") ?></h4>
                                <p class="mb-0"><?= __("address") ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.5s">
                        <div class="office-item p-4 text-center">
                            <div class="office-img mb-4">
                                <i class="fas fa-phone fa-4x text-primary"></i>
                            </div>
                            <div class="office-content d-flex flex-column">
                                <h4 class="mb-2"><?= __("phone") ?></h4>
                                <a href="tel:+37491551987" class="text-secondary fs-5 mb-2"><?= __("phone_number") ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.7s">
                        <div class="office-item p-4 text-center">
                            <div class="office-img mb-4">
                                <i class="fas fa-envelope fa-4x text-primary"></i>
                            </div>
                            <div class="office-content d-flex flex-column">
                                <h4 class="mb-2"><?= __("email") ?></h4>
                                <a href="mailto:<?= htmlspecialchars(__('email_address'), ENT_QUOTES, 'UTF-8') ?>" class="text-muted fs-5 mb-2"><?= __("email_address") ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 pt-5 wow zoomIn" data-wow-delay="0.1s">
                    <div class="rounded h-100">
                        <iframe class="rounded w-100"
                        style="height: 400px;" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12345.6789!2d43.8!3d40.8!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQ4JzAwLjAiTiA0M8KwNDgnMDAuMCJF!5e0!3m2!1sen!2s!4v1234567890!5m2!1sen!2s"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact End -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        const messageDiv = document.getElementById('formMessage');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            submitBtn.disabled = true;
            submitBtn.textContent = '<?= __("sending") ?? 'Sending...' ?>';
            messageDiv.style.display = 'none';
            messageDiv.className = 'mt-3';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('/backend/api/contact.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.ok) {
                    messageDiv.className = 'mt-3 alert alert-success';
                    messageDiv.textContent = result.message;
                    form.reset();
                } else {
                    messageDiv.className = 'mt-3 alert alert-danger';
                    messageDiv.textContent = result.error || '<?= __("error_generic") ?? 'An error occurred' ?>';
                }
            } catch (error) {
                messageDiv.className = 'mt-3 alert alert-danger';
                messageDiv.textContent = '<?= __("error_network") ?? 'Network error. Please try again.' ?>';
            }

            messageDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = '<?= __("send_message") ?>';
        });
    });
    </script>

<?php
include __DIR__ . '/../includes/footer.php';