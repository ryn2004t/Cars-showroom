<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ResponseFactory;
use App\Repositories\CustomerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CustomersController
{
    public function list(Request $request): Response
    {
        try {
            $repo = new CustomerRepository();
            $customers = $repo->listRecent(50);
            return ResponseFactory::json($customers);
        } catch (\Throwable $e) {
            return ResponseFactory::json(['message' => 'Failed to fetch customers'], 500);
        }
    }
}
