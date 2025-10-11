<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\PromotionRepository;
use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PromotionsController
{
    public function list(Request $request): Response
    {
        try {
            $repo = new PromotionRepository();
            $promotions = $repo->listActive();
            return ResponseFactory::json($promotions);
        } catch (\Throwable) {
            return ResponseFactory::json(['message' => 'Failed to fetch promotions'], 500);
        }
    }
}
