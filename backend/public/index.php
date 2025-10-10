<?php

declare(strict_types=1);

use App\Http\Kernel;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../bootstrap.php';

$kernel = new Kernel();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
