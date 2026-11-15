<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Root-absolute media URLs for front (fixes /ru/product/ relative paths).
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function ad_service_normalize_public_row(array $row): array
{
    foreach (['cover', 'icon'] as $field) {
        $path = trim((string) ($row[$field] ?? ''));
        if ($path !== '') {
            $row[$field] = ad_public_upload_url($path);
        }
    }
    return $row;
}

function ad_services_ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $db = ad_db();
    $db->exec(
        "CREATE TABLE IF NOT EXISTS `services` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `title` VARCHAR(180) NOT NULL,
          `slug` VARCHAR(200) NOT NULL,
          `summary` TEXT NULL,
          `content` MEDIUMTEXT NULL,
          `cover` VARCHAR(255) NOT NULL DEFAULT '',
          `icon` VARCHAR(255) NOT NULL DEFAULT '',
          `sort_order` INT NOT NULL DEFAULT 0,
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_services_slug` (`slug`),
          KEY `idx_services_active_sort` (`is_active`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // Add multilingual columns if they don't exist
    $cols = $db->query('SHOW COLUMNS FROM services')->fetchAll(PDO::FETCH_COLUMN);
    $langCols = [
        'title_ru' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'title_hy' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'summary_ru' => "TEXT NULL",
        'summary_hy' => "TEXT NULL",
        'content_ru' => "MEDIUMTEXT NULL",
        'content_hy' => "MEDIUMTEXT NULL",
        'slug_ru' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'slug_hy' => "VARCHAR(200) NOT NULL DEFAULT ''",
        'title_ar' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'summary_ar' => "TEXT NULL",
        'content_ar' => "MEDIUMTEXT NULL",
        'slug_ar' => "VARCHAR(200) NOT NULL DEFAULT ''",
    ];
    foreach ($langCols as $col => $def) {
        if (!in_array($col, $cols, true)) {
            $db->exec("ALTER TABLE services ADD COLUMN `$col` $def");
        }
    }
    $done = true;
}

/**
 * @return array<string, mixed>|null
 */
function ad_service_find_by_slug(string $slug): ?array
{
    ad_services_ensure_schema();
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }
    $stmt = ad_db()->prepare(
        'SELECT * FROM services
         WHERE is_active = 1
           AND (slug = ? OR slug_ru = ? OR slug_hy = ?)
         LIMIT 1'
    );
    $stmt->execute([$slug, $slug, $slug]);
    $row = $stmt->fetch();
    return $row ? ad_service_normalize_public_row($row) : null;
}

/**
 * @return list<array<string, mixed>>
 */
function ad_service_list_active(int $limit = 0): array
{
    ad_services_ensure_schema();
    $sql = 'SELECT id, title, title_ru, title_hy, slug, slug_ru, slug_hy, summary, summary_ru, summary_hy, content, content_ru, content_hy, cover, sort_order, is_active
            FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $rows = ad_db()->query($sql)->fetchAll() ?: [];
    return array_map('ad_service_normalize_public_row', $rows);
}

function ad_service_localized_field(array $row, string $field, string $lang = ''): string
{
    $lang = $lang ?: getCurrentLanguage();
    $suffix = $lang === 'en' ? '' : '_' . $lang;
    $key = $field . $suffix;
    $value = $row[$key] ?? null;
    if ($value === '' || $value === null) {
        return (string) ($row[$field] ?? '');
    }
    return (string) $value;
}

function ad_service_localized_slug(array $row, string $lang = ''): string
{
    unset($lang);
    return trim((string) ($row['slug'] ?? ''));
}

function ad_service_public_url(array $row, string $lang = ''): string
{
    return url('product/' . ad_service_localized_slug($row, $lang), $lang);
}

function ad_service_unique_slug(string $slug, int $excludeId = 0): string
{
    ad_services_ensure_schema();
    $base = ad_slugify($slug);
    $candidate = $base;
    $n = 0;
    $db = ad_db();
    while (true) {
        $stmt = $db->prepare('SELECT id FROM services WHERE slug = ? LIMIT 1');
        $stmt->execute([$candidate]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['id'] === $excludeId) {
            return $candidate;
        }
        $n++;
        $candidate = $base . '-' . $n;
        if (mb_strlen($candidate) > 200) {
            $candidate = mb_substr($base, 0, 190, 'UTF-8') . '-' . $n;
        }
    }
}
