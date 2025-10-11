<?php
// Router for PHP built-in server to serve SPA and API together
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = __DIR__ . $uri;

// Route API to index.php
if (preg_match('#^/api($|/)#', $uri)) {
    require __DIR__ . '/index.php';
    return true;
}

// Serve existing static files (assets, uploads, etc.)
if ($uri !== '/' && file_exists($path) && is_file($path)) {
    return false; // Let the server handle the file
}

// Fallback to SPA index.html
require __DIR__ . '/index.html';
