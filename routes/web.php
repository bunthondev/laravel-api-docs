<?php

use Illuminate\Support\Facades\Route;
use Puchan\LaravelApiDocs\Http\Controllers\ApiDocController;

if (config('api-docs.enabled', true)) {
    $prefix = config('api-docs.route_prefix', 'api-docs');
    $middleware = config('api-docs.middleware', ['web']);

    Route::middleware($middleware)->prefix($prefix)->group(function () {
        Route::get('/', [ApiDocController::class, 'index'])->name('api-docs.index');
        Route::get('/json', [ApiDocController::class, 'json'])->name('api-docs.json');
        Route::get('/swagger', [ApiDocController::class, 'swagger'])->name('api-docs.swagger');
    });
}
