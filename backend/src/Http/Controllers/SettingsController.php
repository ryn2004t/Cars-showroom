<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\SettingsRepository;
use App\Support\RequestValidator;
use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SettingsController
{
    public function get(Request $request): Response
    {
        try {
            $repo = new SettingsRepository();
            return ResponseFactory::json($repo->getAll());
        } catch (\Throwable) {
            return ResponseFactory::json(['message' => 'Failed to fetch settings'], 500);
        }
    }

    public function update(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (!is_array($data)) {
            return ResponseFactory::json(['message' => 'Invalid payload'], 422);
        }
        // Lightweight validation: only string:string pairs allowed
        $map = [];
        foreach ($data as $k => $v) {
            if (!is_string($k) || !is_string($v) || strlen($k) > 64 || strlen($v) > 1024) {
                return ResponseFactory::json(['message' => 'Invalid settings format'], 422);
            }
            $map[$k] = $v;
        }

        try {
            $repo = new SettingsRepository();
            $repo->updateAll($map);
            return ResponseFactory::json(['ok' => true]);
        } catch (\Throwable) {
            return ResponseFactory::json(['message' => 'Failed to update settings'], 500);
        }
    }
}
