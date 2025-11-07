<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Разрешить все источники для виджета (можно ограничить в продакшене)
        $origin = $request->header('Origin') ?? '*';

        // CORS заголовки
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '3600');

        // Разрешить встраивание в iframe
        // Удаляем X-Frame-Options для разрешения встраивания
        $response->headers->remove('X-Frame-Options');
        // Используем Content-Security-Policy для разрешения встраивания на любых доменах
        $response->headers->set('Content-Security-Policy', "frame-ancestors *;");

        // Дополнительные заголовки безопасности для виджета
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        return $response;
    }
}

