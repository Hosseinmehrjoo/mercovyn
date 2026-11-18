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
            'title_ka' => "VARCHAR(200) NOT NULL DEFAULT ''",
            'slug_ka' => "VARCHAR(220) NOT NULL DEFAULT ''",
            'meta_title_ka' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'meta_description_ka' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'meta_keywords_ka' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'excerpt_ka' => 'TEXT NULL',
            'content_ka' => 'MEDIUMTEXT NULL',
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
        $key = 'content_' . $locale;
        if (array_key_exists($key, $post)) {
            $out[$key] = (string) $post[$key];
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

function ad_blog_unique_slug(string $base, int $ignoreId = 0): string
{
    $base = ad_slugify($base);
    if ($base === '') {
        $base = 'draft';
    }
    $slug = $base;
    $n = 2;
    $db = ad_db();
    while (true) {
        $stmt = $db->prepare('SELECT id FROM blog_posts WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$slug, $ignoreId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

/**
 * @param array<string, mixed> $existing
 * @return array<string, string>
 */
function ad_blog_localized_for_autosave(?array $existing, array $input): array
{
    $existing = $existing ?? [];
    $out = [];
    foreach (ad_translate_locales() as $locale) {
        foreach (['title', 'excerpt', 'content', 'meta_title', 'meta_description', 'meta_keywords', 'slug'] as $field) {
            $key = $field . '_' . $locale;
            $out[$key] = (string) ($existing[$key] ?? '');
        }
    }
    foreach (ad_blog_read_manual_locales_from_post($input) as $key => $val) {
        $out[$key] = $val;
    }

    return $out;
}

/**
 * @param array<string, mixed> $input  شبیه $_POST
 * @return array{id:int, slug:string, status:string, saved_at:string, created:bool}
 */
function ad_blog_save_post_from_input(array $input, bool $autosave = false): array
{
    ad_blog_ensure_schema();
    require_once __DIR__ . '/translate.php';

    $id = (int) ($input['id'] ?? 0);
    $existing = null;
    if ($id > 0) {
        $stmt = ad_db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch() ?: null;
        if (!$existing) {
            throw new RuntimeException('نوشته یافت نشد.');
        }
    }

    $title = trim((string) ($input['title'] ?? ''));
    $slug = trim((string) ($input['slug'] ?? ''));
    $excerpt = trim((string) ($input['excerpt'] ?? ''));
    $content = (string) ($input['content'] ?? '');
    $cover = trim((string) ($input['cover_path'] ?? ''));
    $coverAlt = trim((string) ($input['cover_alt'] ?? ''));
    $metaTitle = trim((string) ($input['meta_title'] ?? ''));
    $metaDescription = trim((string) ($input['meta_description'] ?? ''));
    $metaKeywords = trim((string) ($input['meta_keywords'] ?? ''));
    if (mb_strlen($metaTitle) > 255) {
        $metaTitle = mb_substr($metaTitle, 0, 255);
    }
    if (mb_strlen($metaDescription) > 500) {
        $metaDescription = mb_substr($metaDescription, 0, 500);
    }
    if (mb_strlen($metaKeywords) > 500) {
        $metaKeywords = mb_substr($metaKeywords, 0, 500);
    }

    $postedCats = $input['categories'] ?? [];
    if (!is_array($postedCats)) {
        $postedCats = [$postedCats];
    }

    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
    if ($autosave && $id < 1 && $title === '' && $excerpt === '' && $plain === '') {
        $hasLocaleBody = false;
        foreach (ad_translate_locales() as $locale) {
            if (trim(strip_tags((string) ($input['content_' . $locale] ?? ''))) !== '') {
                $hasLocaleBody = true;
                break;
            }
        }
        if (!$hasLocaleBody) {
            throw new RuntimeException('هنوز محتوایی برای ذخیره نیست.');
        }
    }

    if ($title === '') {
        if ($autosave) {
            $title = 'پیش‌نویس ' . date('Y-m-d H:i');
        } else {
            throw new RuntimeException('عنوان الزامی است.');
        }
    }

    if ($slug === '') {
        $slug = ad_slugify($title);
    } else {
        $slug = ad_slugify($slug);
    }
    $slug = ad_blog_unique_slug($slug, $id);

    if ($cover !== '') {
        if (!function_exists('ad_media_set_alt')) {
            require_once __DIR__ . '/media.php';
        }
        ad_media_set_alt($cover, $coverAlt);
    }

    if ($autosave) {
        if ($existing && ($existing['status'] ?? '') === 'published') {
            $status = 'published';
            $publishedAt = $existing['published_at'] ?? null;
        } else {
            $status = 'draft';
            $publishedAt = null;
        }
    } else {
        $status = (($input['status'] ?? 'draft') === 'published') ? 'published' : 'draft';
        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = !empty($existing['published_at'])
                ? (string) $existing['published_at']
                : date('Y-m-d H:i:s');
        }
    }

    $english = [
        'title' => $title,
        'excerpt' => $excerpt,
        'content' => $content,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords' => $metaKeywords,
    ];

    if ($autosave) {
        $localized = ad_blog_localized_for_autosave($existing, $input);
        foreach (ad_translate_locales() as $locale) {
            $slugKey = 'slug_' . $locale;
            if (trim((string) ($localized[$slugKey] ?? '')) === '') {
                $titleLoc = trim((string) ($localized['title_' . $locale] ?? ''));
                $localized[$slugKey] = $titleLoc !== ''
                    ? ad_blog_unique_slug(ad_slugify($titleLoc), $id)
                    : $slug;
            }
        }
    } else {
        $localized = ad_blog_build_localized_fields($english, $existing);
        $localized = ad_blog_merge_locale_fields(
            $localized,
            ad_blog_read_manual_locales_from_post($input),
            $existing
        );
    }

    $created = false;
    if ($id > 0) {
        $stmt = ad_db()->prepare(
            'UPDATE blog_posts SET
                title=?, slug=?, meta_title=?, meta_description=?, meta_keywords=?,
                excerpt=?, content=?, cover=?, status=?, published_at=?,
                title_ru=?, title_hy=?, title_ar=?, title_ka=?, slug_ru=?, slug_hy=?, slug_ar=?, slug_ka=?,
                meta_title_ru=?, meta_title_hy=?, meta_title_ar=?, meta_title_ka=?,
                meta_description_ru=?, meta_description_hy=?, meta_description_ar=?, meta_description_ka=?,
                meta_keywords_ru=?, meta_keywords_hy=?, meta_keywords_ar=?, meta_keywords_ka=?,
                excerpt_ru=?, excerpt_hy=?, excerpt_ar=?, excerpt_ka=?, content_ru=?, content_hy=?, content_ar=?, content_ka=?
             WHERE id=?'
        );
        $stmt->execute([
            $title, $slug, $metaTitle, $metaDescription, $metaKeywords,
            $excerpt, $content, $cover, $status, $publishedAt,
            $localized['title_ru'], $localized['title_hy'], $localized['title_ar'], $localized['title_ka'],
            $localized['slug_ru'], $localized['slug_hy'], $localized['slug_ar'], $localized['slug_ka'],
            $localized['meta_title_ru'], $localized['meta_title_hy'], $localized['meta_title_ar'], $localized['meta_title_ka'],
            $localized['meta_description_ru'], $localized['meta_description_hy'], $localized['meta_description_ar'], $localized['meta_description_ka'],
            $localized['meta_keywords_ru'], $localized['meta_keywords_hy'], $localized['meta_keywords_ar'], $localized['meta_keywords_ka'],
            $localized['excerpt_ru'], $localized['excerpt_hy'], $localized['excerpt_ar'], $localized['excerpt_ka'],
            $localized['content_ru'], $localized['content_hy'], $localized['content_ar'], $localized['content_ka'],
            $id,
        ]);
        ad_blog_post_set_categories($id, $postedCats);
    } else {
        $stmt = ad_db()->prepare(
            'INSERT INTO blog_posts (
                title, slug, meta_title, meta_description, meta_keywords,
                excerpt, content, cover, status, published_at,
                title_ru, title_hy, title_ar, title_ka, slug_ru, slug_hy, slug_ar, slug_ka,
                meta_title_ru, meta_title_hy, meta_title_ar, meta_title_ka,
                meta_description_ru, meta_description_hy, meta_description_ar, meta_description_ka,
                meta_keywords_ru, meta_keywords_hy, meta_keywords_ar, meta_keywords_ka,
                excerpt_ru, excerpt_hy, excerpt_ar, excerpt_ka, content_ru, content_hy, content_ar, content_ka
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $title, $slug, $metaTitle, $metaDescription, $metaKeywords,
            $excerpt, $content, $cover, $status, $publishedAt,
            $localized['title_ru'], $localized['title_hy'], $localized['title_ar'], $localized['title_ka'],
            $localized['slug_ru'], $localized['slug_hy'], $localized['slug_ar'], $localized['slug_ka'],
            $localized['meta_title_ru'], $localized['meta_title_hy'], $localized['meta_title_ar'], $localized['meta_title_ka'],
            $localized['meta_description_ru'], $localized['meta_description_hy'], $localized['meta_description_ar'], $localized['meta_description_ka'],
            $localized['meta_keywords_ru'], $localized['meta_keywords_hy'], $localized['meta_keywords_ar'], $localized['meta_keywords_ka'],
            $localized['excerpt_ru'], $localized['excerpt_hy'], $localized['excerpt_ar'], $localized['excerpt_ka'],
            $localized['content_ru'], $localized['content_hy'], $localized['content_ar'], $localized['content_ka'],
        ]);
        $id = (int) ad_db()->lastInsertId();
        ad_blog_post_set_categories($id, $postedCats);
        $created = true;
    }

    if (!$autosave) {
        require_once __DIR__ . '/seo.php';
        ad_seo_on_content_change();
    }

    return [
        'id' => $id,
        'slug' => $slug,
        'status' => $status,
        'saved_at' => date('c'),
        'created' => $created,
    ];
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
    if ($lang === '') {
        $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    }
    $base = trim((string) ($post['slug'] ?? ''));
    if ($lang === 'en') {
        return $base;
    }
    $key = 'slug_' . $lang;
    $localized = trim((string) ($post[$key] ?? ''));

    return $localized !== '' ? $localized : $base;
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
        return ad_public_copy((string) ($row[$field] ?? ''), $lang);
    }
    return ad_public_copy((string) $value, $lang);
}

/**
 * Locale column only — no English fallback. Empty string if that language was never stored.
 */
function ad_blog_locale_only(array $row, string $field, string $lang): string
{
    if ($lang === '' || $lang === 'en') {
        return trim((string) ($row[$field] ?? ''));
    }

    return trim((string) ($row[$field . '_' . $lang] ?? ''));
}

/**
 * Fill missing title / meta title / meta description / excerpt for one language and save them.
 * Body HTML is not translated here.
 *
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function ad_blog_ensure_locale_seo(array $post, string $lang): array
{
    if ($lang === '' || $lang === 'en' || !in_array($lang, ad_supported_languages(), true)) {
        return $post;
    }
    $id = (int) ($post['id'] ?? 0);
    if ($id < 1) {
        return $post;
    }
    if (!function_exists('ad_translate_text')) {
        require_once __DIR__ . '/translate.php';
    }
    if (!ad_translate_enabled()) {
        return $post;
    }

    $map = [
        'title' => trim((string) ($post['title'] ?? '')),
        'meta_title' => trim((string) ($post['meta_title'] ?? '')),
        'meta_description' => trim((string) ($post['meta_description'] ?? '')),
        'excerpt' => trim((string) ($post['excerpt'] ?? '')),
    ];
    if ($map['meta_title'] === '') {
        $map['meta_title'] = $map['title'];
    }
    if ($map['meta_description'] === '') {
        $map['meta_description'] = $map['excerpt'];
    }

    $updates = [];
    foreach ($map as $field => $source) {
        $key = $field . '_' . $lang;
        if (trim((string) ($post[$key] ?? '')) !== '' || $source === '') {
            continue;
        }
        $translated = trim(ad_translate_text($source, $lang));
        if ($translated === '' || $translated === $source) {
            continue;
        }
        $post[$key] = $translated;
        $updates[$key] = $translated;
    }

    if ($updates === []) {
        return $post;
    }

    $sets = [];
    $params = [];
    foreach ($updates as $column => $value) {
        $sets[] = '`' . $column . '` = ?';
        $params[] = $value;
    }
    $params[] = $id;
    ad_db()->prepare('UPDATE blog_posts SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    return $post;
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
        'ka' => ['იან', 'თებ', 'მარ', 'აპრ', 'მაი', 'ივნ', 'ივლ', 'აგვ', 'სექ', 'ოქტ', 'ნოე', 'დეკ'],
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
