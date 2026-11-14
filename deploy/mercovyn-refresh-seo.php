<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/backend/lib/db.php';
require $root . '/backend/lib/seo.php';

ad_seo_refresh_public_files();
echo "robots ensured; legacy static sitemap.xml/llms.txt removed if present\n";
echo "Live URLs: /sitemap.xml and /llms.txt (dynamic via index.php)\n";
