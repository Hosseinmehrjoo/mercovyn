<?php
require_once __DIR__ . '/../router.php';

$fabPhone = __('phone_number');
$fabPhoneHref = 'tel:' . preg_replace('/\s+/', '', $fabPhone);
$fabEmail = __('email_address');
$fabWhatsapp = __('whatsapp_link');
$fabTelegram = __('telegram_link');
$fabLabelOpen = __('fab_contact_open');
$fabLabelClose = __('fab_contact_close');

$fabRadius = 88;
$fabActions = [
    [
        'href' => $fabPhoneHref,
        'class' => 'fab-contact__item--phone',
        'icon' => 'fas fa-phone-alt',
        'title' => __('phone'),
        'label' => __('call_us') . ' ' . $fabPhone,
        'external' => false,
    ],
    [
        'href' => 'mailto:' . $fabEmail,
        'class' => 'fab-contact__item--email',
        'icon' => 'fas fa-envelope',
        'title' => __('email'),
        'label' => __('email'),
        'external' => false,
    ],
    [
        'href' => $fabWhatsapp,
        'class' => 'fab-contact__item--whatsapp',
        'icon' => 'fab fa-whatsapp',
        'title' => 'WhatsApp',
        'label' => 'WhatsApp',
        'external' => true,
    ],
];

if ($fabTelegram !== '' && $fabTelegram !== 'telegram_link') {
    $fabActions[] = [
        'href' => $fabTelegram,
        'class' => 'fab-contact__item--telegram',
        'icon' => 'fab fa-telegram-plane',
        'title' => 'Telegram',
        'label' => 'Telegram',
        'external' => true,
    ];
}

$fabCount = count($fabActions);
/* Arc on upper-left only; items never sit below the toggle center */
$fabArcStart = 88;
$fabArcEnd = 178;
?>
<div class="fab-contact" id="fabContact" data-open="false" style="--fab-radius: <?= (int) $fabRadius ?>px">
    <div class="fab-contact__backdrop" id="fabContactBackdrop" aria-hidden="true"></div>
    <ul class="fab-contact__actions" id="fabContactActions" role="list" aria-hidden="true">
        <?php foreach ($fabActions as $i => $action):
            if ($fabCount === 1) {
                $angle = ($fabArcStart + $fabArcEnd) / 2;
            } else {
                $angle = $fabArcStart + (($fabArcEnd - $fabArcStart) * $i / ($fabCount - 1));
            }
            $rad = deg2rad($angle);
            $fabTx = (int) round(cos($rad) * $fabRadius);
            $fabTy = (int) round(-sin($rad) * $fabRadius);
            if ($fabTy > 0) {
                $fabTy = 0;
            }
            ?>
        <li role="none">
            <a href="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>"
               class="fab-contact__item <?= htmlspecialchars($action['class'], ENT_QUOTES, 'UTF-8') ?>"
               style="--fab-i: <?= (int) $i ?>; --fab-tx: <?= $fabTx ?>px; --fab-ty: <?= $fabTy ?>px"
               title="<?= htmlspecialchars($action['title'], ENT_QUOTES, 'UTF-8') ?>"
               aria-label="<?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>"
               <?php if ($action['external']): ?>
               target="_blank"
               rel="noopener noreferrer"
               <?php endif; ?>>
                <i class="<?= htmlspecialchars($action['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <button type="button"
            class="fab-contact__toggle btn border-0 rounded-circle shadow"
            id="fabContactToggle"
            aria-expanded="false"
            aria-controls="fabContactActions"
            data-label-open="<?= htmlspecialchars($fabLabelOpen, ENT_QUOTES, 'UTF-8') ?>"
            data-label-close="<?= htmlspecialchars($fabLabelClose, ENT_QUOTES, 'UTF-8') ?>">
        <span class="fab-contact__icon fab-contact__icon--open" aria-hidden="true"><i class="fas fa-phone-alt"></i></span>
        <span class="fab-contact__icon fab-contact__icon--close" aria-hidden="true"><i class="fas fa-times"></i></span>
    </button>
</div>
