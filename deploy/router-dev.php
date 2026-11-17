<?php
/**
 * Router for: php -S 127.0.0.1:8765 deploy/router-dev.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($uri !== '/' && $uri !== '') {
    $file = $root . $uri;
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

chdir($root);
require $root . '/index.php';