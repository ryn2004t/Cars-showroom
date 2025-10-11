<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ResponseFactory
{
    public static function json(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}
