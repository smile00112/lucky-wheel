<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class WidgetAssetController extends Controller
{
    public function __invoke(string $path): Response
    {
        $basePath = realpath(public_path('js/widget'));
        if ($basePath === false) {
            abort(404);
        }

        $cleanPath = ltrim($path, '/');
        if (str_contains($cleanPath, '..')) {
            abort(404);
        }

        $fullPath = $basePath . DIRECTORY_SEPARATOR . $cleanPath;
        $resolvedPath = realpath($fullPath);

        if ($resolvedPath === false || !str_starts_with($resolvedPath, $basePath) || !File::exists($resolvedPath)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'js' => 'application/javascript',
            'css' => 'text/css',
            'json' => 'application/json',
            default => File::mimeType($resolvedPath) ?: 'application/octet-stream',
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

        $response = response(
            File::get($resolvedPath),
            200,
            $headers
        );

        return $response;
    }
}


