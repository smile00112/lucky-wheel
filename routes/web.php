<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WidgetController;

Route::get('/', function () {
    return view('welcome');
});
//if (app()->environment('local')){}
Route::get('/wheel', [WidgetController::class, 'show'])
    ->name('wheel.show');

// Веб-маршрут для виджета (для iframe)
Route::get('/widget/embed/{slug}', [WidgetController::class, 'embed'])
    ->name('widget.embed.web');
