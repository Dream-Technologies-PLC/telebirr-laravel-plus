<?php

use DreamTechnologies\TelebirrLaravelPlus\Http\Controllers\TelebirrController;
use Illuminate\Support\Facades\Route;

Route::middleware(config(
    'telebirr.client_route_middleware',
    config('telebirr.route_middleware', ['api'])
))->group(function (): void {
    Route::post('/create-order', [TelebirrController::class, 'createOrder'])->name('telebirr.create-order');
    Route::post('/query-order', [TelebirrController::class, 'queryOrder'])->name('telebirr.query-order');
});

Route::middleware(config('telebirr.notify_route_middleware', ['api', 'throttle:60,1']))
    ->post('/notify', [TelebirrController::class, 'notify'])
    ->name('telebirr.notify');
