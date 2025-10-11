<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\AnalyticsRepository;
use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsController
{
    public function summary(Request $request): Response
    {
        try {
            $repo = new AnalyticsRepository();
            $summary = $repo->getSummary();
            return ResponseFactory::json($summary);
        } catch (\Throwable) {
            return ResponseFactory::json(['message' => 'Failed to fetch analytics'], 500);
        }
    }
}
