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
        // Обработка preflight OPTIONS запросов
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflight($request);
        }

        $response = $next($request);

        // Получаем Origin из запроса
        $origin = $request->header('Origin');

        // Если Origin указан, используем его, иначе разрешаем все
        // Примечание: если нужны credentials, используем конкретный origin, иначе '*'
        $allowedOrigin = $origin ?: '*';

        // CORS заголовки
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Origin');
        
        // Если origin указан, можно использовать credentials, иначе нет
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        
        $response->headers->set('Access-Control-Max-Age', '86400');

        // Дополнительные заголовки безопасности для виджета
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        return $response;
    }

    /**
     * Обработка preflight OPTIONS запросов
     */
    protected function handlePreflight(Request $request): Response
    {
        $origin = $request->header('Origin');
        $allowedOrigin = $origin ?: '*';

        $response = response('', 200);

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, Origin');
        
        // Если origin указан, можно использовать credentials, иначе нет
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}

