<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class JobsController
{
    public function list(Request $request): Response
    {
        // Stub list; replace with DB access later
        $jobs = [
            [
                'id' => 'job-1',
                'title' => 'Oil Change',
                'scheduledAt' => '2025-10-11T09:00:00Z',
                'status' => 'confirmed',
                'customerId' => 'cust-1',
            ],
            [
                'id' => 'job-2',
                'title' => 'Brake Inspection',
                'scheduledAt' => '2025-10-12T14:30:00Z',
                'status' => 'requested',
                'customerId' => 'cust-2',
            ],
        ];
        return ResponseFactory::json($jobs);
    }
}
