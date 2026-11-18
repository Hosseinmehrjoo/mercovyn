<?php

require_once __DIR__ . '/db.php';

function ad_gallery_ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    ad_db()->exec(
        "CREATE TABLE IF NOT EXISTS `site_gallery_images` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `title` VARCHAR(255) NOT NULL DEFAULT '',
          `title_ru` VARCHAR(255) NOT NULL DEFAULT '',
          `title_hy` VARCHAR(255) NOT NULL DEFAULT '',
          `title_ar` VARCHAR(255) NOT NULL DEFAULT '',
          `image_url` VARCHAR(500) NOT NULL DEFAULT '',
          `sort_order` INT NOT NULL DEFAULT 0,
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_gallery_active_sort` (`is_active`, `sort_order`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $col = ad_db()->query("SHOW COLUMNS FROM site_gallery_images LIKE 'title_ka'")->fetch();
    if (!$col) {
        ad_db()->exec("ALTER TABLE site_gallery_images ADD COLUMN title_ka VARCHAR(255) NOT NULL DEFAULT '' AFTER title_ar");
    }
    $done = true;
}

/**
 * @return array<string, mixed>|null
 */
function ad_gallery_get(int $id): ?array
{
    ad_gallery_ensure_schema();
    if ($id < 1) {
        return null;
    }
    $stmt = ad_db()->prepare('SELECT * FROM site_gallery_images WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function ad_gallery_list_all(): array
{
    ad_gallery_ensure_schema();

    return ad_db()->query(
        'SELECT * FROM site_gallery_images ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
}

/**
 * @return list<array<string, mixed>>
 */
function ad_gallery_list_public(): array
{
    ad_gallery_ensure_schema();

    return ad_db()->query(
        "SELECT * FROM site_gallery_images
         WHERE is_active = 1 AND image_url <> ''
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll();
}

function ad_gallery_localized_title(array $row, string $lang = ''): string
{
    if ($lang === '') {
        $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    }
    if ($lang === 'en') {
        $t = trim((string) ($row['title'] ?? ''));

        return $t !== '' ? $t : '';
    }
    $key = 'title_' . $lang;
    $t = trim((string) ($row[$key] ?? ''));
    if ($t !== '') {
        return $t;
    }

    return trim((string) ($row['title'] ?? ''));
}