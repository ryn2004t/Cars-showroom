<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MediaController
{
    /**
     * Accepts multipart/form-data with field 'file' and optional 'jobId'.
     */
    public function upload(Request $request): Response
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return ResponseFactory::json(['message' => 'No file uploaded'], 400);
        }

        // Validate size (10MB) and mime types
        if ($file->getSize() !== null && $file->getSize() > 10 * 1024 * 1024) {
            return ResponseFactory::json(['message' => 'File too large'], 413);
        }

        $allowed = [
            'image/jpeg', 'image/png', 'image/webp',
            'video/mp4', 'video/quicktime'
        ];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return ResponseFactory::json(['message' => 'Unsupported file type'], 415);
        }

        $jobId = (string)($request->request->get('jobId') ?? '');
        if ($jobId !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $jobId)) {
            return ResponseFactory::json(['message' => 'Invalid jobId'], 422);
        }

        $safeName = bin2hex(random_bytes(8)) . '.' . $file->guessExtension();
        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $file->move($uploadDir, $safeName);

        // In a real app: persist record linking jobId and path
        return ResponseFactory::json([
            'id' => 'media-' . bin2hex(random_bytes(4)),
            'jobId' => $jobId ?: null,
            'path' => '/uploads/' . $safeName,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ], 201);
    }
}
