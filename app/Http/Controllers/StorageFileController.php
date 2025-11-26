<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorageFileController extends Controller
{
    public function __invoke(Request $request, string $path): Response
    {

        // Обработка OPTIONS запросов (preflight)
        if ($request->isMethod('OPTIONS')) {
            $origin = $request->header('Origin');
            $allowedOrigin = $origin ?: '*';

            $headers = [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, Origin',
                'Access-Control-Max-Age' => '86400',
            ];

            if ($origin) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }

            return response('', 200, $headers);
        }
        // Безопасность: проверяем путь
        $cleanPath = ltrim($path, '/');
        if (str_contains($cleanPath, '..')) {
            abort(404);
        }

        // Проверяем, существует ли файл в public storage
        if (!Storage::disk('public')->exists($cleanPath)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($cleanPath);

        if (!File::exists($fullPath)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            default => File::mimeType($fullPath) ?: 'application/octet-stream',
        };

        $origin = request()->header('Origin');
        $allowedOrigin = $origin ?: '*';

        $headers = [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Origin',
            'Cache-Control' => 'public, max-age=31536000',
        ];

        if ($origin) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return response(
            File::get($fullPath),
            200,
            $headers
        );
    }
}

