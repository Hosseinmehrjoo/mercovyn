<?php
/**
 * Background: translate blog post body (content → content_ru/hy/ar).
 * Usage: php backend/jobs/translate_blog_post.php <post_id>
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$id = (int) ($argv[1] ?? 0);
if ($id < 1) {
    fwrite(STDERR, "Usage: translate_blog_post.php <post_id>\n");
    exit(1);
}

require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/blog.php';

$result = ad_blog_translate_post_body($id);
if (!empty($result['warnings'])) {
    foreach ($result['warnings'] as $w) {
        fwrite(STDERR, $w . "\n");
    }
}

exit($result['ok'] ? 0 : 1);