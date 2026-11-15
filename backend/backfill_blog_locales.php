<?php
/**
 * پر کردن مجدد content_{locale} برای نوشته‌هایی که بدنه ترجمه نشده یا کپی انگلیسی است.
 * php backend/backfill_blog_locales.php
 * php backend/backfill_blog_locales.php --locale=ar
 * php backend/backfill_blog_locales.php --dry-run
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);
$onlyLocale = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--locale=')) {
        $onlyLocale = substr($arg, 9);
    }
}

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/translate.php';

ad_blog_ensure_schema();

if (!ad_translate_enabled()) {
    fwrite(STDERR, "auto_translate is off in config.php\n");
    exit(1);
}

$locales = ad_translate_locales();
if ($onlyLocale !== null) {
    if (!in_array($onlyLocale, $locales, true)) {
        fwrite(STDERR, "Unknown locale: {$onlyLocale}\n");
        exit(1);
    }
    $locales = [$onlyLocale];
}

$db = ad_db();
$rows = $db->query('SELECT * FROM blog_posts ORDER BY id ASC')->fetchAll() ?: [];
$updated = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $title = (string) ($row['title'] ?? '');
    $content = (string) ($row['content'] ?? '');
    if (trim($content) === '') {
        continue;
    }

    foreach ($locales as $locale) {
        if (!ad_blog_locale_content_missing($row, $locale)) {
            continue;
        }
        echo "#{$id} \"{$title}\" → content_{$locale}\n";
        if ($dryRun) {
            $updated++;
            continue;
        }

        @set_time_limit(900);
        $translated = ad_translate_text($content, $locale, ad_translate_settings()['source']);
        if (trim($translated) === '' || $translated === $content) {
            fwrite(STDERR, "  skip: translation failed or unchanged\n");
            foreach (ad_translate_take_warnings() as $w) {
                fwrite(STDERR, "  warn: {$w}\n");
            }
            continue;
        }

        $col = 'content_' . $locale;
        $stmt = $db->prepare("UPDATE blog_posts SET `{$col}` = ? WHERE id = ?");
        $stmt->execute([$translated, $id]);
        $row[$col] = $translated;
        foreach (ad_translate_take_warnings() as $w) {
            fwrite(STDERR, "  warn: {$w}\n");
        }
        $updated++;
        usleep(250000);
    }
}

echo $dryRun
    ? "Dry run: {$updated} translation job(s).\n"
    : "Done: {$updated} content field(s) updated.\n";