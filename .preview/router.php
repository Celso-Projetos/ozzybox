<?php
declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = __DIR__ . '/..' . $path;
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

if ($path === '/' || (in_array($path, [
    '/index.html', '/404.html', '/robots.txt', '/sitemap.xml', '/convenios.php',
    '/beneficios.json', '/beneficios.js', '/beneficios-admin.css', '/site.js', '/style.css',
], true) && is_file($file))
    || (preg_match('~\A/assets/(?:[a-z0-9_-]+/)*[a-z0-9_.-]+\.(?:svg|jpe?g|png|webp|avif|woff2?|ico)\z~i', $path)
        && is_file($file))) {
    return false;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile(__DIR__ . '/../404.html');
