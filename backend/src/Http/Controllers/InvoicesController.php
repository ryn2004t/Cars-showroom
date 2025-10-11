<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\InvoiceRepository;
use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class InvoicesController
{
    public function list(Request $request): Response
    {
        try {
            $repo = new InvoiceRepository();
            $invoices = $repo->listRecent(50);
            return ResponseFactory::json($invoices);
        } catch (\Throwable) {
            return ResponseFactory::json(['message' => 'Failed to fetch invoices'], 500);
        }
    }
}
