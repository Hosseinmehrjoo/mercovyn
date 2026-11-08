<?php
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    header('Location: ../install.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/router.php';
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

ad_session_start();
ad_security_headers();
