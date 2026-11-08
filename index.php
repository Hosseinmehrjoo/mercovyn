<?php
/**
 * Central Router - Entry point for all requests
 * Handles language detection and routes to appropriate page
 */

require_once __DIR__ . '/router.php';

ad_security_headers();

$currentLang = getCurrentLanguage();
$langInfo = getLanguageInfo();

// Load translations
$translations = loadTranslations($currentLang);

// Define page routes (without language prefix)
$routes = [
    '' => 'pages/home.php',
    'about' => 'about.html',
    'products' => 'pages/products.php',
    'product' => 'pages/product.php',
    'service' => 'pages/products.php',
    'services' => 'pages/products.php',
    'contact' => 'pages/contact.php',
    'feature' => 'feature.html',
    'features' => 'feature.html',
    'countries' => 'countries.html',
    'testimonial' => 'testimonial.html',
    'testimonials' => 'testimonial.html',
    'training' => 'training.html',
    'blog' => 'blog.php',
    'blog-single' => 'blog-single.php',
    '404' => '404.html',
];

// Get requested path (without language prefix)
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path = trim($path, '/');

// Remove language prefix from path
$segments = explode('/', $path);
if (in_array($segments[0] ?? '', SUPPORTED_LANGUAGES, true)) {
    array_shift($segments);
}

// Rebuild clean path after language removal
$cleanPath = implode('/', $segments);
$cleanPath = trim($cleanPath, '/');

// Single product: /product/{slug}/
if (count($segments) >= 2 && $segments[0] === 'product' && $segments[1] !== '') {
    $_GET['slug'] = $segments[1];
    $pageFile = 'pages/product.php';
} elseif (count($segments) >= 2 && $segments[0] === 'blog-single' && $segments[1] !== '') {
    $_GET['slug'] = $segments[1];
    $pageFile = 'blog-single.php';
} elseif (count($segments) >= 2 && $segments[0] === 'blog' && $segments[1] !== '') {
    // Blog category or pagination
    $_GET['category'] = $segments[1];
    $pageFile = 'blog.php';
} else {
    $pageFile = $routes[$cleanPath] ?? null;
}

// If no route found, try to serve as static file
if ($pageFile === null) {
    // Check if it's a static file request (css, js, images, etc.)
    // Use clean path (without language prefix) for static files
    $staticFile = __DIR__ . '/' . $cleanPath;
    if (is_file($staticFile) && !str_ends_with($staticFile, '.php')) {
        // Serve static file
        $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        // Cache static assets for 1 year
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($staticFile);
        exit;
    }
    
    // 404
    http_response_code(404);
    $pageFile = '404.html';
}

// Make variables available to included files
$lang = $currentLang;
$langPrefix = $langInfo['prefix'];
$hreflangUrls = getHreflangUrls($cleanPath);
$translations = $translations;

// Include the page
include __DIR__ . '/' . $pageFile;