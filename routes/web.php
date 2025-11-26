<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\WidgetAssetController;
use App\Http\Controllers\StorageFileController;

Route::get('/', function () {
    return view('welcome');
});
//if (app()->environment('local')){}
Route::get('/wheel', [WidgetController::class, 'show'])
    ->name('wheel.show');

// Веб-маршрут для виджета (для iframe)
Route::get('/widget/embed/{slug}', [WidgetController::class, 'embed'])
    ->name('widget.embed.web');

// Веб-маршрут для виджета v2 (новая модульная версия)
Route::get('/widget/embed-v2/{slug}', [WidgetController::class, 'embedV2'])
    ->name('widget.embed.v2');

Route::get('/widget/assets/{path}', WidgetAssetController::class)
    ->where('path', '.*')
    ->name('widget.assets');

// Маршрут для отдачи storage файлов с CORS заголовками
Route::get('/storage/prizes/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('storage.file');

// Telegram WebApp
Route::get('/telegram/app', [App\Http\Controllers\TelegramController::class, 'webapp'])
    ->name('telegram.webapp');

// Telegram Webhook (без CSRF защиты)
Route::post('/telegram/{integration}/webhook', [App\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
