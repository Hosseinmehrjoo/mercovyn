<?php
/**
 * Language Switcher — inline flags + codes (no dropdown)
 */

require_once __DIR__ . '/../router.php';

$langInfo = getLanguageInfo();
$currentLang = $langInfo['current'];
$currentPath = $currentPath ?? '';
$hreflangPathByLang = $hreflangPathByLang ?? [];
if (!isset($hreflangUrls) || !is_array($hreflangUrls)) {
    $hreflangUrls = getHreflangUrls($currentPath, $hreflangPathByLang);
}

$langSwitcherVariant = $langSwitcherVariant ?? 'topbar';
$switcherRootClass = match ($langSwitcherVariant) {
    'navbar' => 'language-switcher language-switcher--navbar',
    'mobile-header' => 'language-switcher language-switcher--mobile-header',
    default => 'language-switcher',
};

$appendLangPicked = static function (string $absoluteUrl): string {
    $parts = parse_url($absoluteUrl);
    if ($parts === false || empty($parts['scheme'])) {
        return $absoluteUrl;
    }
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['lang_picked'] = '1';
    $path = $parts['path'] ?? '/';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $q = http_build_query($query);

    return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $port . $path . '?' . $q;
};

$languageLabels = [
    'en' => ['label' => 'EN', 'name' => 'English', 'flag' => '🇺🇸'],
    'ru' => ['label' => 'RU', 'name' => 'Русский', 'flag' => '🇷🇺'],
    'hy' => ['label' => 'HY', 'name' => 'Հայերեն', 'flag' => '🇦🇲'],
    'ar' => ['label' => 'AR', 'name' => 'العربية', 'flag' => ''],
    'ka' => ['label' => 'KA', 'name' => 'ქართული', 'flag' => '🇬🇪'],
];
?>

<nav class="<?= htmlspecialchars($switcherRootClass, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('language'), ENT_QUOTES, 'UTF-8') ?>">
    <ul class="language-switcher__list list-unstyled mb-0 d-inline-flex align-items-center">
        <?php foreach ($languageLabels as $code => $info): ?>
            <li>
                <a class="language-switcher__btn<?= $code === $currentLang ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($appendLangPicked($hreflangUrls[$code] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                   hreflang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                   lang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                   title="<?= htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8') ?>"
                   <?php if ($code === $currentLang): ?>aria-current="true"<?php endif; ?>>
                    <?php if ($info['flag'] !== ''): ?>
                    <span class="language-switcher__flag" aria-hidden="true"><?= $info['flag'] ?></span>
                    <?php endif; ?>
                    <span class="language-switcher__code"><?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
