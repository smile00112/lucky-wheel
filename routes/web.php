<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\WidgetAssetController;
use App\Http\Controllers\StorageFileController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\TestSpinNotificationController;
use App\Http\Middleware\WidgetCors;

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

// Веб-маршрут для виджета v3 (новая версия с отдельным шаблоном)
Route::get('/widget/embed-v3/{slug}', [WidgetController::class, 'embedV3'])
    ->name('widget.embed.v3');

// Веб-маршрут для виджета v3 (новая версия с отдельным шаблоном)
Route::get('/widget/vk-v3/{slug}', [WidgetController::class, 'vkV3'])
    ->name('widget.vk.v3');

Route::get('/widget/assets/{path}', WidgetAssetController::class)
    ->where('path', '.*')
    ->name('widget.assets');

// Маршрут для отдачи storage файлов с CORS заголовками
Route::get('/storage/prizes/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('storage.file');

// Маршрут для проксирования изображений призов с CORS заголовками
Route::get('/img/{path}', ImageProxyController::class)
    ->where('path', '.*')
    ->middleware(WidgetCors::class)
    ->name('image.proxy');

// Telegram WebApp
Route::get('/telegram/app', [App\Http\Controllers\TelegramController::class, 'webapp'])
    ->name('telegram.webapp');

// Telegram Webhook (без CSRF защиты)
Route::post('/telegram/{integration}/webhook', [App\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// VK Mini App
Route::get('/vk/app', [App\Http\Controllers\VKController::class, 'webapp'])
    ->name('vk.webapp');

// VK Callback API (без CSRF защиты)
Route::post('/vk/{integration}/webhook', [App\Http\Controllers\VKWebhookController::class, 'handle'])
    ->name('vk.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// Тестовый роут для отправки письма о призе
Route::get('/test/prize-email/{prizeId}', function ($prizeId) {
    $prize = \App\Models\Prize::findOrFail($prizeId);

    // Создаем или находим тестового гостя
    $guest = \App\Models\Guest::firstOrCreate(
        ['email' => 'gorely.aleksei@yandex.ru'],
        [
            'name' => 'Тестовый пользователь',
            'phone' => '+79991234567',
        ]
    );

    $results = [];
    $errors = [];

    // Список всех шаблонов для отправки
    $mailClasses = [
        'spa-prize-win' => \App\Mail\SpaPrizeWinMail::class,
        'spa-prize-win-elegant' => \App\Mail\SpaPrizeWinElegantMail::class,
        'spa-prize-win-modern' => \App\Mail\SpaPrizeWinModernMail::class,
        'spa-prize-win-vibrant' => \App\Mail\SpaPrizeWinVibrantMail::class,
    ];

    foreach ($mailClasses as $templateName => $mailClass) {
        try {
            // Создаем тестовый Spin для каждого письма
            $spin = \App\Models\Spin::create([
                'wheel_id' => $prize->wheel_id,
                'guest_id' => $guest->id,
                'prize_id' => $prize->id,
                'code' => \App\Models\Spin::generateUniqueCode(),
                'email_notification' => false,
            ]);

            // Отправляем письмо
            \Illuminate\Support\Facades\Mail::to('gorely.aleksei@yandex.ru')
                ->send(new $mailClass($spin));

            $results[] = [
                'template' => $templateName,
                'status' => 'success',
                'spin_id' => $spin->id,
            ];
        } catch (\Exception $e) {
            $errors[] = [
                'template' => $templateName,
                'error' => $e->getMessage(),
            ];
        }
    }

    return response()->json([
        'success' => count($errors) === 0,
        'message' => count($results) . ' писем отправлено, ' . count($errors) . ' ошибок',
        'results' => $results,
        'errors' => $errors,
        'prize_name' => $prize->name,
    ]);
})->name('test.prize-email');

// Тестовый роут для отправки сообщения о выигрыше гостю
Route::get('/test/spin/{spinId}/send-notification', [TestSpinNotificationController::class, 'sendNotification'])
    ->name('test.spin.send-notification');
