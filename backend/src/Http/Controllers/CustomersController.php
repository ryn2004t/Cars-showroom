<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CustomersController
{
    public function list(Request $request): Response
    {
        // Stub list; replace with DB access later
        $customers = [
            [ 'id' => 'cust-1', 'name' => 'Alex Johnson', 'phone' => '+1 555-0100' ],
            [ 'id' => 'cust-2', 'name' => 'Maria Garcia', 'phone' => '+1 555-0111' ],
        ];
        return ResponseFactory::json($customers);
    }
}
