<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WidgetController;

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
    
    // API для получения истории вращений гостя
    Route::get('/guest/{guestId}/spins', [WidgetController::class, 'getGuestSpins'])
        ->name('widget.guest.spins');
});

