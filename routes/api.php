<?php


use App\Http\Controllers\UrlController;
use Illuminate\Support\Facades\Route;


Route::middleware('throttle:60,1')->group(function () {
    Route::post('/shorten', [UrlController::class, 'shorten']);
    Route::get('/{alias}', [UrlController::class, 'redirect'])->where('alias', '[A-Za-z0-9-]+');
    Route::get('/analytics/{alias}', [UrlController::class, 'analytics'])->where('alias', '[A-Za-z0-9-]+');
});
