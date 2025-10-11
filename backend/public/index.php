<?php

declare(strict_types=1);

use App\Http\Kernel;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// If this is a request for the SPA entry or a static asset, let router handle
if ($uri !== '/' && !preg_match('#^/api($|/)#', $uri)) {
    // The built-in server will serve files directly; fallback to router for SPA
    if (php_sapi_name() === 'cli-server') {
        return require __DIR__ . '/router.php';
    }
}

$kernel = new Kernel();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
