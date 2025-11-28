<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\TelegramController;

Route::prefix('widget')->group(function () {
    // OPTIONS для CORS preflight
    Route::options('/{any}', [WidgetController::class, 'options'])->where('any', '.*');
    
    // API для получения данных колеса
    Route::get('/wheel/{slug}', [WidgetController::class, 'getWheel'])
        ->name('widget.wheel');
    
    // API для создания/получения гостя
    Route::post('/guest', [WidgetController::class, 'createOrGetGuest'])
        ->name('widget.guest');
    
    // API для выполнения вращения
    Route::post('/spin', [WidgetController::class, 'spin'])
        ->name('widget.spin');
    
    // API для завершения вращения (после окончания анимации)
    Route::post('/spin/{spinId}/complete', [WidgetController::class, 'completeSpin'])
        ->name('widget.spin.complete');
    
    // API для получения истории вращений гостя
    Route::get('/guest/{guestId}/spins', [WidgetController::class, 'getGuestSpins'])
        ->name('widget.guest.spins');
    
    // API для получения сегодняшнего выигрыша
    Route::get('/wheel/{slug}/today-win', [WidgetController::class, 'getTodayWin'])
        ->name('widget.today-win');
    
    // API для получения информации о госте
    Route::get('/guest/{guestId}/info', [WidgetController::class, 'getGuestInfo'])
        ->name('widget.guest.info');
    
    // API для обновления данных гостя
    Route::put('/guest/{guestId}', [WidgetController::class, 'updateGuest'])
        ->name('widget.guest.update');
    
    // API для сохранения данных гостя и отправки приза
    Route::post('/guest/{guestId}/claim-prize', [WidgetController::class, 'claimPrize'])
        ->name('widget.guest.claim-prize');
    
    // API для отправки приза на почту (без ввода данных, только по spin_id)
    Route::post('/spin/{spinId}/send-email', [WidgetController::class, 'sendPrizeEmail'])
        ->name('widget.spin.send-email');
    
    // API для скачивания PDF сертификата выигрыша
    Route::get('/spin/{spinId}/download-pdf', [WidgetController::class, 'downloadWinPdf'])
        ->name('widget.spin.download-pdf');
});

// Telegram API
Route::prefix('telegram')->group(function () {
    Route::post('/auth', [TelegramController::class, 'auth'])
        ->name('telegram.auth');
    
    Route::post('/spin', [TelegramController::class, 'spin'])
        ->name('telegram.spin');
});

