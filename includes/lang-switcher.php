<?php
/**
 * Language Switcher — inline flags + codes (no dropdown)
 */

require_once __DIR__ . '/../router.php';

$langInfo = getLanguageInfo();
$currentLang = $langInfo['current'];
$currentPath = $currentPath ?? '';
$hreflangUrls = getHreflangUrls($currentPath);

$languageLabels = [
    'en' => ['label' => 'EN', 'name' => 'English', 'flag' => '🇺🇸'],
    'ru' => ['label' => 'RU', 'name' => 'Русский', 'flag' => '🇷🇺'],
    'hy' => ['label' => 'HY', 'name' => 'Հայերեն', 'flag' => '🇦🇲'],
];
?>

<nav class="language-switcher" aria-label="<?= htmlspecialchars(__('language'), ENT_QUOTES, 'UTF-8') ?>">
    <ul class="language-switcher__list list-unstyled mb-0 d-inline-flex align-items-center">
        <?php foreach ($languageLabels as $code => $info): ?>
            <li>
                <a class="language-switcher__btn<?= $code === $currentLang ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($hreflangUrls[$code] ?? '#', ENT_QUOTES, 'UTF-8') ?>"
                   hreflang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                   lang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                   title="<?= htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8') ?>"
                   <?php if ($code === $currentLang): ?>aria-current="true"<?php endif; ?>>
                    <span class="language-switcher__flag" aria-hidden="true"><?= $info['flag'] ?></span>
                    <span class="language-switcher__code"><?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
