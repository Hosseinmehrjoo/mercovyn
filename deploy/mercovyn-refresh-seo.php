<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/backend/lib/db.php';
require $root . '/backend/lib/seo.php';

ad_seo_refresh_public_files();
echo "sitemap and robots refreshed\n";
