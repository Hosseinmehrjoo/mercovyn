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
          `sort_order` INT NOT NULL DEFAULT 0,
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_site_videos_active_sort` (`is_active`, `sort_order`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $done = true;
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
    return trim((string) ($row['title'] ?? ''));
}

/**
 * @return list<array<string, mixed>>
 */
function ad_videos_list_public(string $lang = 'en'): array
{
    ad_videos_ensure_schema();
    $rows = ad_db()->query(
        "SELECT * FROM site_videos WHERE is_active = 1 AND video_url <> '' ORDER BY sort_order ASC, id ASC"
    )->fetchAll();
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
            'video_url' => ad_public_upload_url((string) $row['video_url']),
            'poster_url' => ad_public_upload_url((string) $row['poster_url']),
            'subtitles' => $subs,
        ];
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