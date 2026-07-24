<?php

use Illuminate\Support\Facades\Route;
use Zerp\ProductService\Http\Controllers\Api\CategoryApiController;
use Zerp\ProductService\Http\Controllers\Api\DashboardApiController;
use Zerp\ProductService\Http\Controllers\Api\ProductServiceItemApiController;
use Zerp\ProductService\Http\Controllers\Api\TaxApiController;
use Zerp\ProductService\Http\Controllers\Api\UnitApiController;

Route::prefix('api')->middleware(['api.json'])->group(function () {
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'product-service-catalog', 'as' => 'api.product-service-catalog.'], function () {
        Route::get('dashboard', [DashboardApiController::class, 'index'])->name('dashboard');

        Route::apiResource('items', ProductServiceItemApiController::class);
        Route::apiResource('categories', CategoryApiController::class);
        Route::apiResource('units', UnitApiController::class);
        Route::apiResource('taxes', TaxApiController::class);
    });
});
