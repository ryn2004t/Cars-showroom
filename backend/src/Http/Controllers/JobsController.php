<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ResponseFactory;
use App\Repositories\JobRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class JobsController
{
    public function list(Request $request): Response
    {
        try {
            $repo = new JobRepository();
            $jobs = $repo->listRecent(50);
            return ResponseFactory::json($jobs);
        } catch (\Throwable $e) {
            return ResponseFactory::json(['message' => 'Failed to fetch jobs'], 500);
        }
    }
}
