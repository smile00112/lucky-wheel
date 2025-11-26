<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageProxyController extends Controller
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

        // Декодируем путь из base64url
        try {
            $decodedPath = base64_decode(str_replace(['-', '_'], ['+', '/'], $path));
            if ($decodedPath === false || empty($decodedPath)) {
                // Если не base64, пробуем как обычный путь
                $decodedPath = urldecode($path);
            }
        } catch (\Exception $e) {
            // Если ошибка декодирования, используем путь как есть
            $decodedPath = urldecode($path);
        }
        
        // Если путь - это полный URL, проксируем его
        if (filter_var($decodedPath, FILTER_VALIDATE_URL)) {
            return $this->proxyExternalImage($decodedPath, $request);
        }

        // Если путь начинается с /, это абсолютный путь - конвертируем в полный URL
        if (str_starts_with($decodedPath, '/')) {
            $fullUrl = url($decodedPath);
            if (filter_var($fullUrl, FILTER_VALIDATE_URL)) {
                return $this->proxyExternalImage($fullUrl, $request);
            }
        }

        // Иначе ищем в storage
        return $this->serveStorageImage($decodedPath, $request);
    }

    protected function proxyExternalImage(string $url, Request $request): Response
    {
        try {
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                abort(404, 'Image not found');
            }

            $content = $response->body();
            $contentType = $response->header('Content-Type') ?: 'image/jpeg';

            $origin = $request->header('Origin');
            $allowedOrigin = $origin ?: '*';

            $headers = [
                'Content-Type' => $contentType,
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, Origin',
                'Cache-Control' => 'public, max-age=31536000',
            ];

            if ($origin) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }

            return response($content, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Image proxy error: ' . $e->getMessage(), ['url' => $url]);
            abort(404, 'Failed to load image');
        }
    }

    protected function serveStorageImage(string $path, Request $request): Response
    {
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
            default => File::mimeType($fullPath) ?: 'application/octet-stream',
        };

        $origin = $request->header('Origin');
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

