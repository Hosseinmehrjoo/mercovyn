<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function ad_blog_ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $db = ad_db();
    $db->exec(
        "CREATE TABLE IF NOT EXISTS `blog_categories` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(120) NOT NULL,
          `slug` VARCHAR(140) NOT NULL,
          `description` TEXT NULL,
          `parent_id` INT UNSIGNED NULL DEFAULT NULL,
          `sort_order` INT NOT NULL DEFAULT 0,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_blog_category_slug` (`slug`),
          KEY `idx_blog_category_parent` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS `blog_post_categories` (
          `post_id` INT UNSIGNED NOT NULL,
          `category_id` INT UNSIGNED NOT NULL,
          PRIMARY KEY (`post_id`, `category_id`),
          KEY `idx_bpc_category` (`category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    try {
        $cols = $db->query('SHOW COLUMNS FROM blog_posts')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        $cols = [];
    }
    if ($cols && !in_array('meta_title', $cols, true)) {
        $db->exec("ALTER TABLE blog_posts ADD COLUMN `meta_title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `slug`");
        $cols[] = 'meta_title';
    }
    if ($cols && !in_array('meta_description', $cols, true)) {
        $db->exec("ALTER TABLE blog_posts ADD COLUMN `meta_description` VARCHAR(500) NOT NULL DEFAULT '' AFTER `meta_title`");
        $cols[] = 'meta_description';
    }
    if ($cols && !in_array('meta_keywords', $cols, true)) {
        $db->exec("ALTER TABLE blog_posts ADD COLUMN `meta_keywords` VARCHAR(500) NOT NULL DEFAULT '' AFTER `meta_description`");
        $cols[] = 'meta_keywords';
    }
    if ($cols) {
        $blogLangCols = [
            'title_ru' => "VARCHAR(200) NOT NULL DEFAULT ''",
            'title_hy' => "VARCHAR(200) NOT NULL DEFAULT ''",
            'slug_ru' => "VARCHAR(220) NOT NULL DEFAULT ''",
            'slug_hy' => "VARCHAR(220) NOT NULL DEFAULT ''",
            'meta_title_ru' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'meta_title_hy' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'meta_description_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'meta_description_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'meta_keywords_ru' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'meta_keywords_hy' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'excerpt_ru' => 'TEXT NULL',
            'excerpt_hy' => 'TEXT NULL',
            'content_ru' => 'MEDIUMTEXT NULL',
            'content_hy' => 'MEDIUMTEXT NULL',
            'title_ar' => "VARCHAR(200) NOT NULL DEFAULT ''",
            'slug_ar' => "VARCHAR(220) NOT NULL DEFAULT ''",
            'meta_title_ar' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'meta_description_ar' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'meta_keywords_ar' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'excerpt_ar' => 'TEXT NULL',
            'content_ar' => 'MEDIUMTEXT NULL',
        ];
        foreach ($blogLangCols as $col => $def) {
            if (!in_array($col, $cols, true)) {
                $db->exec("ALTER TABLE blog_posts ADD COLUMN `$col` $def");
                $cols[] = $col;
            }
        }
    }
    $done = true;
}

/** @return list<string> */
function ad_blog_locale_form_fields(): array
{
    return ['title', 'excerpt', 'content', 'meta_title', 'meta_description', 'meta_keywords', 'slug'];
}

/**
 * فقط فیلدهای کوتاه (عنوان، خلاصه، متا) — بدنهٔ مقاله ترجمه خودکار نمی‌شود.
 *
 * @param array<string, string> $english
 * @param array<string, mixed>|null $existing
 * @return array<string, string>
 */
function ad_blog_build_localized_fields(array $english, ?array $existing = null): array
{
    if (!function_exists('ad_auto_translate_record')) {
        require_once __DIR__ . '/translate.php';
    }
    $light = ['title', 'excerpt', 'meta_title', 'meta_description', 'meta_keywords'];
    $localized = ad_auto_translate_record($english, $light, $existing, []);

    foreach (ad_translate_locales() as $locale) {
        $localized['content_' . $locale] = '';
        if (!isset($localized['slug_' . $locale])) {
            $localized['slug_' . $locale] = '';
        }
    }

    return $localized;
}

/**
 * @param array<string, mixed> $post
 * @return array<string, string>
 */
function ad_blog_read_manual_locales_from_post(array $post): array
{
    $out = [];
    foreach (ad_translate_locales() as $locale) {
        foreach (ad_blog_locale_form_fields() as $field) {
            $key = $field . '_' . $locale;
            if (!array_key_exists($key, $post)) {
                continue;
            }
            $out[$key] = $field === 'content' ? (string) $post[$key] : trim((string) $post[$key]);
        }
    }

    return $out;
}

/**
 * ترکیب ترجمهٔ خودکار فیلدهای سبک با مقادیر دستی (به‌ویژه بدنهٔ HTML).
 *
 * @param array<string, string> $auto
 * @param array<string, string> $manual
 * @param array<string, mixed>|null $existing
 * @return array<string, string>
 */
function ad_blog_merge_locale_fields(array $auto, array $manual, ?array $existing = null): array
{
    $existing = $existing ?? [];
    $merged = $auto;
    $light = ['title', 'excerpt', 'meta_title', 'meta_description', 'meta_keywords'];

    foreach (ad_translate_locales() as $locale) {
        foreach ($light as $field) {
            $key = $field . '_' . $locale;
            $man = (string) ($manual[$key] ?? '');
            if (trim($man) !== '') {
                $merged[$key] = $man;
            } elseif (!isset($merged[$key]) || trim((string) $merged[$key]) === '') {
                $merged[$key] = trim((string) ($existing[$key] ?? ''));
            }
        }

        $contentKey = 'content_' . $locale;
        $merged[$contentKey] = array_key_exists($contentKey, $manual)
            ? (string) $manual[$contentKey]
            : (string) ($existing[$contentKey] ?? '');

        $slugKey = 'slug_' . $locale;
        $slugMan = trim((string) ($manual[$slugKey] ?? ''));
        if ($slugMan !== '') {
            $merged[$slugKey] = ad_slugify($slugMan);
        } else {
            $titleLoc = trim((string) ($merged['title_' . $locale] ?? ''));
            $merged[$slugKey] = $titleLoc !== ''
                ? ad_slugify($titleLoc)
                : ad_slugify((string) ($existing['slug'] ?? $existing['title'] ?? ''));
        }
    }

    return $merged;
}

/**
 * Translate English post body into content_{locale} columns (CLI / background).
 *
 * @return array{ok:bool,warnings:list<string>}
 */
function ad_blog_translate_post_body(int $id): array
{
    ad_blog_ensure_schema();
    if ($id < 1 || !ad_translate_enabled()) {
        return ['ok' => false, 'warnings' => ['ترجمه غیرفعال یا شناسه نامعتبر.']];
    }
    $stmt = ad_db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'warnings' => ['نوشته یافت نشد.']];
    }
    if (!function_exists('ad_translate_text')) {
        require_once __DIR__ . '/translate.php';
    }

    $content = (string) ($row['content'] ?? '');
    if (trim($content) === '') {
        return ['ok' => true, 'warnings' => []];
    }

    @set_time_limit(0);
    $sets = [];
    $params = [];
    foreach (ad_translate_locales() as $locale) {
        $translated = ad_translate_text($content, $locale, ad_translate_settings()['source']);
        if (trim($translated) === '') {
            continue;
        }
        $col = 'content_' . $locale;
        $sets[] = '`' . $col . '` = ?';
        $params[] = $translated;
    }
    if ($sets === []) {
        return ['ok' => false, 'warnings' => ad_translate_take_warnings()];
    }
    $params[] = $id;
    ad_db()->prepare('UPDATE blog_posts SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    return ['ok' => true, 'warnings' => ad_translate_take_warnings()];
}

/**
 * @return list<array<string, mixed>>
 */
function ad_blog_categories_list(): array
{
    ad_blog_ensure_schema();
    return ad_db()->query(
        'SELECT c.*,
                (SELECT COUNT(*) FROM blog_post_categories pc WHERE pc.category_id = c.id) AS post_count
         FROM blog_categories c
         ORDER BY c.sort_order ASC, c.name ASC'
    )->fetchAll();
}

function ad_blog_category_get(int $id): ?array
{
    ad_blog_ensure_schema();
    if ($id < 1) {
        return null;
    }
    $stmt = ad_db()->prepare('SELECT * FROM blog_categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function ad_blog_category_unique_slug(string $base, int $ignoreId = 0): string
{
    $slug = ad_slugify($base);
    $db = ad_db();
    $candidate = $slug;
    $i = 2;
    while (true) {
        $stmt = $db->prepare('SELECT id FROM blog_categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$candidate]);
        $row = $stmt->fetch();
        if (!$row || ((int) $row['id'] === $ignoreId)) {
            return $candidate;
        }
        $candidate = mb_substr($slug, 0, 120, 'UTF-8') . '-' . $i;
        $i++;
    }
}

/**
 * @return array<string, mixed>
 */
function ad_blog_category_create(string $name, string $slug = '', string $description = '', ?int $parentId = null): array
{
    ad_blog_ensure_schema();
    $name = trim($name);
    if ($name === '') {
        throw new RuntimeException('نام دسته‌بندی الزامی است.');
    }
    if (mb_strlen($name) > 120) {
        $name = mb_substr($name, 0, 120);
    }
    $slug = trim($slug) !== '' ? ad_slugify($slug) : ad_blog_category_unique_slug($name);
    $slug = ad_blog_category_unique_slug($slug);
    $description = trim($description);
    $parent = ($parentId !== null && $parentId > 0) ? $parentId : null;
    if ($parent !== null && !ad_blog_category_get($parent)) {
        $parent = null;
    }
    $stmt = ad_db()->prepare(
        'INSERT INTO blog_categories (name, slug, description, parent_id, sort_order) VALUES (?,?,?,?,0)'
    );
    $stmt->execute([$name, $slug, $description, $parent]);
    $id = (int) ad_db()->lastInsertId();
    $row = ad_blog_category_get($id);
    if (!$row) {
        throw new RuntimeException('ایجاد دسته‌بندی ممکن نشد.');
    }
    return $row;
}

function ad_blog_category_update(int $id, string $name, string $slug = '', string $description = '', ?int $parentId = null): void
{
    ad_blog_ensure_schema();
    $existing = ad_blog_category_get($id);
    if (!$existing) {
        throw new RuntimeException('دسته‌بندی یافت نشد.');
    }
    $name = trim($name);
    if ($name === '') {
        throw new RuntimeException('نام دسته‌بندی الزامی است.');
    }
    $slug = trim($slug) !== '' ? ad_slugify($slug) : ad_blog_category_unique_slug($name, $id);
    $slug = ad_blog_category_unique_slug($slug, $id);
    $description = trim($description);
    $parent = ($parentId !== null && $parentId > 0) ? $parentId : null;
    if ($parent === $id) {
        $parent = null;
    }
    if ($parent !== null && !ad_blog_category_get($parent)) {
        $parent = null;
    }
    $stmt = ad_db()->prepare(
        'UPDATE blog_categories SET name=?, slug=?, description=?, parent_id=? WHERE id=?'
    );
    $stmt->execute([$name, $slug, $description, $parent, $id]);
}

function ad_blog_category_delete(int $id): void
{
    ad_blog_ensure_schema();
    if ($id < 1) {
        return;
    }
    $db = ad_db();
    $db->prepare('UPDATE blog_categories SET parent_id = NULL WHERE parent_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM blog_post_categories WHERE category_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM blog_categories WHERE id = ?')->execute([$id]);
}

/**
 * @return list<int>
 */
function ad_blog_post_category_ids(int $postId): array
{
    ad_blog_ensure_schema();
    if ($postId < 1) {
        return [];
    }
    $stmt = ad_db()->prepare('SELECT category_id FROM blog_post_categories WHERE post_id = ?');
    $stmt->execute([$postId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * @param list<int|string> $categoryIds
 */
function ad_blog_post_set_categories(int $postId, array $categoryIds): void
{
    ad_blog_ensure_schema();
    if ($postId < 1) {
        return;
    }
    $db = ad_db();
    $db->prepare('DELETE FROM blog_post_categories WHERE post_id = ?')->execute([$postId]);
    $seen = [];
    $ins = $db->prepare('INSERT INTO blog_post_categories (post_id, category_id) VALUES (?,?)');
    foreach ($categoryIds as $raw) {
        $cid = (int) $raw;
        if ($cid < 1 || isset($seen[$cid])) {
            continue;
        }
        if (!ad_blog_category_get($cid)) {
            continue;
        }
        $seen[$cid] = true;
        $ins->execute([$postId, $cid]);
    }
}

/**
 * @return list<array{id:int,name:string,slug:string}>
 */
function ad_blog_post_categories(int $postId): array
{
    ad_blog_ensure_schema();
    if ($postId < 1) {
        return [];
    }
    $stmt = ad_db()->prepare(
        'SELECT c.id, c.name, c.slug
         FROM blog_categories c
         INNER JOIN blog_post_categories pc ON pc.category_id = c.id
         WHERE pc.post_id = ?
         ORDER BY c.name ASC'
    );
    $stmt->execute([$postId]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
        ];
    }
    return $out;
}

/**
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function ad_blog_post_slug(array $post, string $lang = ''): string
{
    unset($lang);
    return trim((string) ($post['slug'] ?? ''));
}

function ad_blog_localized_field(array $row, string $field, string $lang = ''): string
{
    if ($lang === '') {
        $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    }
    $suffix = $lang === 'en' ? '' : '_' . $lang;
    $key = $field . $suffix;
    $value = $row[$key] ?? null;
    if ($value === '' || $value === null) {
        return (string) ($row[$field] ?? '');
    }
    return (string) $value;
}

/**
 * True when localized HTML body is missing or still identical to English source.
 */
function ad_blog_locale_content_missing(array $row, string $locale): bool
{
    if ($locale === '' || $locale === 'en') {
        return false;
    }
    $key = 'content_' . $locale;
    $localized = trim((string) ($row[$key] ?? ''));
    $base = (string) ($row['content'] ?? '');
    if ($localized === '') {
        return trim($base) !== '';
    }

    return $localized === $base;
}

function ad_blog_format_date(?string $dateStr, string $lang = ''): string
{
    $dateStr = trim((string) $dateStr);
    if ($dateStr === '') {
        return '';
    }
    if ($lang === '') {
        $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
    if (!$dt) {
        return $dateStr;
    }
    $months = [
        'en' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'ru' => ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        'hy' => ['Հուն', 'Փետ', 'Մար', 'Ապր', 'Մայ', 'Հուն', 'Հուլ', 'Օգս', 'Սեպ', 'Հոկ', 'Նոյ', 'Դեկ'],
        'ar' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
    ];
    $langMonths = $months[$lang] ?? $months['en'];
    $m = (int) $dt->format('m') - 1;

    return $dt->format('d') . ' ' . $langMonths[$m] . ' ' . $dt->format('Y');
}

function ad_blog_normalize_post(array $post): array
{
    $id = (int) ($post['id'] ?? 0);
    $post['meta_title'] = trim((string) ($post['meta_title'] ?? ''));
    $post['meta_description'] = trim((string) ($post['meta_description'] ?? ''));
    $post['meta_keywords'] = trim((string) ($post['meta_keywords'] ?? ''));
    $post['categories'] = ad_blog_post_categories($id);
    if (!function_exists('ad_comments_count_approved')) {
        require_once __DIR__ . '/comments.php';
    }
    $post['comment_count'] = ad_comments_count_approved($id);
    if (!function_exists('ad_media_with_cover_alt')) {
        require_once __DIR__ . '/media.php';
    }
    return ad_media_with_cover_alt($post);
}
