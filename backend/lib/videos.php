<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function ad_videos_ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $db = ad_db();
    $db->exec(
        "CREATE TABLE IF NOT EXISTS `site_videos` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `title` VARCHAR(255) NOT NULL DEFAULT '',
          `title_ru` VARCHAR(255) NOT NULL DEFAULT '',
          `title_hy` VARCHAR(255) NOT NULL DEFAULT '',
          `video_url` VARCHAR(1000) NOT NULL DEFAULT '',
          `poster_url` VARCHAR(1000) NOT NULL DEFAULT '',
          `subtitle_srt_en` VARCHAR(500) NOT NULL DEFAULT '',
          `subtitle_srt_ru` VARCHAR(500) NOT NULL DEFAULT '',
          `subtitle_srt_hy` VARCHAR(500) NOT NULL DEFAULT '',
          `placement` VARCHAR(32) NOT NULL DEFAULT 'home',
          `sort_order` INT NOT NULL DEFAULT 0,
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_site_videos_active_sort` (`is_active`, `sort_order`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $col = $db->query("SHOW COLUMNS FROM site_videos LIKE 'placement'")->fetch();
    if (!$col) {
        $db->exec(
            "ALTER TABLE site_videos ADD COLUMN placement VARCHAR(32) NOT NULL DEFAULT 'home' AFTER subtitle_srt_hy"
        );
        $db->exec(
            'ALTER TABLE site_videos ADD KEY idx_site_videos_placement (placement, is_active, sort_order, id)'
        );
    }
    $titleAr = $db->query("SHOW COLUMNS FROM site_videos LIKE 'title_ar'")->fetch();
    if (!$titleAr) {
        $db->exec(
            "ALTER TABLE site_videos ADD COLUMN title_ar VARCHAR(255) NOT NULL DEFAULT '' AFTER title_hy"
        );
    }
    $titleKa = $db->query("SHOW COLUMNS FROM site_videos LIKE 'title_ka'")->fetch();
    if (!$titleKa) {
        $db->exec(
            "ALTER TABLE site_videos ADD COLUMN title_ka VARCHAR(255) NOT NULL DEFAULT '' AFTER title_ar"
        );
    }
    $done = true;
}

/** @return array<string, string> */
function ad_video_placement_options(): array
{
    return [
        'home' => 'صفحه اصلی (کاروسل — زیر Our Advantages)',
        'about' => 'درباره ما (زیر سکشن اصلی)',
        'hero' => 'ویدیو هیرو (پس‌زمینه هیرو، پخش خودکار)',
    ];
}

/** @var array<string, string> placement slug → DB placement to load */
function ad_video_placement_aliases(): array
{
    return [
        // همان ویدیوی(های) جایگاه «درباره ما»، بدون رکورد جدا در پنل
        'home_products' => 'about',
    ];
}

function ad_video_placement_resolve(string $placement): string
{
  $placement = trim($placement);
    $aliases = ad_video_placement_aliases();

    return $aliases[$placement] ?? $placement;
}

function ad_video_placement_valid(string $placement): bool
{
    if (array_key_exists($placement, ad_video_placement_options())) {
        return true;
    }

    return array_key_exists($placement, ad_video_placement_aliases());
}

function ad_videos_get(int $id): ?array
{
    ad_videos_ensure_schema();
    if ($id < 1) {
        return null;
    }
    $stmt = ad_db()->prepare('SELECT * FROM site_videos WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function ad_videos_list_all(): array
{
    ad_videos_ensure_schema();
    return ad_db()->query('SELECT * FROM site_videos ORDER BY sort_order ASC, id ASC')->fetchAll();
}

/**
 * Normalize streaming URL (e.g. uupload ?play for video/mp4).
 */
function ad_video_normalize_stream_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url) && preg_match('#uupload\.ir/#i', $url)) {
        if (!preg_match('/[?&]play(?:=|$)/', $url)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'play';
        }
    }
    return $url;
}

function ad_video_localized_title(array $row, string $lang): string
{
    if ($lang === 'ru') {
        $t = trim((string) ($row['title_ru'] ?? ''));
        if ($t !== '') {
            return $t;
        }
    }
    if ($lang === 'hy') {
        $t = trim((string) ($row['title_hy'] ?? ''));
        if ($t !== '') {
            return $t;
        }
    }
    if ($lang === 'ar') {
        $t = trim((string) ($row['title_ar'] ?? ''));
        if ($t !== '') {
            return $t;
        }
    }
    if ($lang === 'ka') {
        $t = trim((string) ($row['title_ka'] ?? ''));
        if ($t !== '') {
            return $t;
        }
    }
    return trim((string) ($row['title'] ?? ''));
}

/**
 * @return list<array<string, mixed>>
 */
function ad_videos_list_public(string $lang = 'en', string $placement = 'home', ?int $limit = null): array
{
    ad_videos_ensure_schema();
    if (!ad_video_placement_valid($placement)) {
        return [];
    }
    $dbPlacement = ad_video_placement_resolve($placement);
    $stmt = ad_db()->prepare(
        "SELECT * FROM site_videos WHERE is_active = 1 AND video_url <> '' AND placement = ?
         ORDER BY sort_order ASC, id ASC"
    );
    $stmt->execute([$dbPlacement]);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $subs = [];
        foreach (['en', 'ru', 'hy'] as $code) {
            $key = 'subtitle_srt_' . $code;
            if (trim((string) ($row[$key] ?? '')) !== '') {
                $subs[$code] = '/backend/api/subtitle.php?id=' . $id . '&lang=' . $code;
            }
        }
        $out[] = [
            'id' => $id,
            'title' => ad_video_localized_title($row, $lang),
            'video_url' => ad_video_normalize_stream_url(ad_public_upload_url((string) $row['video_url'])),
            'poster_url' => ad_public_upload_url((string) $row['poster_url']),
            'subtitles' => $subs,
        ];
    }
    if ($limit !== null && $limit > 0) {
        $out = array_slice($out, 0, $limit);
    }

    return $out;
}

function ad_video_subtitle_path(array $video, string $lang): string
{
    if (!in_array($lang, ['en', 'ru', 'hy'], true)) {
        return '';
    }
    $key = 'subtitle_srt_' . $lang;
    return trim((string) ($video[$key] ?? ''));
}