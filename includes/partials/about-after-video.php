<?php
/**
 * About page — stats & export timeline (Mercovyn; not template demo content).
 */
?>
    <!-- Counter -->
    <section class="container-fluid counter-facts py-5">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-12 col-sm-6 col-xl-3 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="counter">
                        <div class="counter-icon"><i class="fas fa-globe"></i></div>
                        <div class="counter-content">
                            <h3><?= __('happy_clients') ?></h3>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="counter-value" data-toggle="counter-up">6</span>
                                <h4 class="text-secondary mb-0" style="font-weight:600;font-size:25px">+</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="counter">
                        <div class="counter-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="counter-content">
                            <h3><?= __('projects_done') ?></h3>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="counter-value" data-toggle="counter-up">20</span>
                                <h4 class="text-secondary mb-0" style="font-weight:600;font-size:25px">+</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="counter">
                        <div class="counter-icon"><i class="fas fa-seedling"></i></div>
                        <div class="counter-content">
                            <h3><?= __('team_members') ?></h3>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="counter-value" data-toggle="counter-up">800</span>
                                <h4 class="text-secondary mb-0" style="font-weight:600;font-size:25px">+</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 wow fadeInUp" data-wow-delay="0.7s">
                    <div class="counter">
                        <div class="counter-icon"><i class="fas fa-dumbbell"></i></div>
                        <div class="counter-content">
                            <h3><?= __('awards_won') ?></h3>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="counter-value" data-toggle="counter-up">70</span>
                                <h4 class="text-secondary mb-0" style="font-weight:600;font-size:25px">kg</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Export timeline (same story as homepage) -->
    <section class="container-fluid py-5 bg-light">
        <div class="container py-5">
            <div class="section-title text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">
                <div class="sub-style">
                    <h5 class="sub-title text-primary px-3"><?= __('about_export_kicker') ?></h5>
                </div>
                <h2 class="display-5 mb-4"><?= __('about_export_title') ?></h2>
                <p class="mb-0"><?= __('about_export_desc') ?></p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php
                $exportTimeline = [
                    ['year' => '2014', 'key' => 'export_georgia', 'desc' => 'about_export_georgia_desc'],
                    ['year' => '2016', 'key' => 'export_turkmenistan', 'desc' => 'about_export_turkmenistan_desc'],
                    ['year' => '2018', 'key' => 'export_iraq', 'desc' => 'about_export_iraq_desc'],
                    ['year' => '2021', 'key' => 'export_uzbekistan', 'desc' => 'about_export_uzbekistan_desc'],
                    ['year' => '2023', 'key' => 'export_tajikistan', 'desc' => 'about_export_tajikistan_desc'],
                    ['year' => '2024', 'key' => 'export_armenia', 'desc' => 'about_export_armenia_desc', 'highlight' => true],
                ];
                $delay = 0.1;
                foreach ($exportTimeline as $item):
                    $isHi = !empty($item['highlight']);
                    ?>
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="<?= $delay ?>s">
                    <div class="p-4 rounded shadow-sm h-100 text-center <?= $isHi ? 'bg-primary text-white' : 'bg-white' ?>">
                        <h2 class="<?= $isHi ? 'text-white' : 'text-primary' ?> mb-1"><?= $item['year'] ?></h2>
                        <h5 class="mb-2"><?= __($item['key']) ?></h5>
                        <p class="<?= $isHi ? 'text-white-50' : 'text-muted' ?> small mb-0"><?= __($item['desc']) ?></p>
                    </div>
                </div>
                <?php
                    $delay += 0.1;
                endforeach; ?>
            </div>
            <div class="text-center mt-5 wow fadeInUp">
                <a href="<?= url('contact') ?>" class="btn btn-primary rounded-pill px-5 py-3"><?= __('get_quote') ?></a>
            </div>
        </div>
    </section>