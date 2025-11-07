<?php

use Illuminate\Support\Facades\Route;
use App\Models\Wheel;
use App\Http\Controllers\WidgetController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/wheel', function () {
    // Получаем первое активное колесо или первое доступное
    $wheel = Wheel::where('is_active', true)
        ->with('activePrizes')
        ->first();
    
    // Если нет активного колеса, берем первое доступное
    if (!$wheel) {
        $wheel = Wheel::first();
    }
    
    // Если нет колеса вообще, возвращаем ошибку
    if (!$wheel) {
        abort(404, 'No wheel found');
    }
    
    // Используем шаблон виджета
    return view('widget.wheel', compact('wheel'));
});

// Веб-маршрут для виджета (для iframe)
Route::get('/widget/embed/{slug}', [WidgetController::class, 'embed'])
    ->name('widget.embed.web');
