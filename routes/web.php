<?php

use Illuminate\Support\Facades\Route;
use App\Models\Wheel;
use App\Http\Controllers\WidgetController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/wheel', function () {
    // Получаем первое активное колесо или создаем тестовое
    $wheel = Wheel::where('is_active', true)
        ->with('activePrizes')
        ->first();
    
    // Если нет колеса, создаем тестовое
    if (!$wheel) {
        $wheel = Wheel::first();
    }
    
    return view('wheel', compact('wheel'));
});

// Веб-маршрут для виджета (для iframe)
Route::get('/widget/embed/{slug}', [WidgetController::class, 'embed'])
    ->name('widget.embed.web');
