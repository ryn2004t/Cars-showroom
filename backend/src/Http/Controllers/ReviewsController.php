<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\ReviewRepository;
use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReviewsController
{
    public function list(Request $request): Response
    {
        try {
            $repo = new ReviewRepository();
            $reviews = $repo->listRecent(50);
            return ResponseFactory::json($reviews);
        } catch (\Throwable) {
            return ResponseFactory::json(['message' => 'Failed to fetch reviews'], 500);
        }
    }
}
