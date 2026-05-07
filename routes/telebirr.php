<?php

use DreamTechnologies\TelebirrLaravelPlus\Http\Controllers\TelebirrController;
use Illuminate\Support\Facades\Route;

Route::post('/create-order', [TelebirrController::class, 'createOrder'])->name('telebirr.create-order');
Route::post('/query-order', [TelebirrController::class, 'queryOrder'])->name('telebirr.query-order');
Route::post('/notify', [TelebirrController::class, 'notify'])->name('telebirr.notify');
