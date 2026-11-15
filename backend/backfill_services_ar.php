<?php
/**
 * پر کردن فیلدهای عربی محصولات خالی — با همان منطق ذخیرهٔ پنل (نیاز به auto_translate روشن).
 * استفاده: php backend/backfill_services_ar.php
 *          php backend/backfill_services_ar.php --dry-run
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv ?? [], true);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/services.php';
require_once __DIR__ . '/lib/translate.php';

ad_services_ensure_schema();

if (!ad_translate_enabled()) {
    fwrite(STDERR, "auto_translate is off in config.php — enable it first.\n");
    exit(1);
}

$db = ad_db();
$rows = $db->query('SELECT * FROM services ORDER BY id ASC')->fetchAll() ?: [];
$updated = 0;

foreach ($rows as $row) {
    $id = (int) $row['id'];
    if (trim((string) ($row['title_ar'] ?? '')) !== '') {
        continue;
    }
    $english = [
        'title' => (string) ($row['title'] ?? ''),
        'summary' => (string) ($row['summary'] ?? ''),
        'content' => (string) ($row['content'] ?? ''),
    ];
    if (trim($english['title']) === '') {
        continue;
    }

    echo "Product #{$id}: {$english['title']}\n";
    if ($dryRun) {
        $updated++;
        continue;
    }

    @set_time_limit(300);
    $localized = ad_auto_translate_record($english, ['title', 'summary', 'content'], $row, []);
    $slug = trim((string) ($row['slug'] ?? ''));

    $stmt = $db->prepare(
        'UPDATE services SET title_ar=?, summary_ar=?, content_ar=?, slug_ar=? WHERE id=?'
    );
    $stmt->execute([
        $localized['title_ar'] ?? '',
        $localized['summary_ar'] ?? '',
        $localized['content_ar'] ?? '',
        $slug,
        $id,
    ]);
    foreach (ad_translate_take_warnings() as $w) {
        fwrite(STDERR, "  warn: {$w}\n");
    }
    $updated++;
    usleep(200000);
}

echo $dryRun
    ? "Dry run: {$updated} product(s) would be updated.\n"
    : "Done: {$updated} product(s) updated with Arabic.\n";