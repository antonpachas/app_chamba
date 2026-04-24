<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\Client\FavoriteController;
use App\Http\Controllers\Api\V1\Client\ReviewController;
use App\Http\Controllers\Api\V1\Client\ServiceRequestController;
use App\Http\Controllers\Api\V1\GeoController;
use App\Http\Controllers\Api\V1\Provider\DashboardController;
use App\Http\Controllers\Api\V1\Provider\ProfileController;
use App\Http\Controllers\Api\V1\Provider\ServiceController;
use App\Http\Controllers\Api\V1\ServiceSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [RegisterController::class, 'store']);
    Route::post('auth/login', [LoginController::class, 'store']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('geo/departments', [GeoController::class, 'departments']);
    Route::get('geo/provinces', [GeoController::class, 'provinces']);
    Route::get('geo/districts', [GeoController::class, 'districts']);
    Route::get('services/search', [ServiceSearchController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [LogoutController::class, 'store']);
        Route::get('auth/me', [MeController::class, 'show']);

        Route::middleware('role:proveedor')->prefix('provider')->group(function (): void {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::post('profile', [ProfileController::class, 'store']);
            Route::put('profile', [ProfileController::class, 'update']);

            Route::get('services', [ServiceController::class, 'index']);
            Route::post('services', [ServiceController::class, 'store']);
            Route::put('services/{service}', [ServiceController::class, 'update']);
            Route::patch('services/{service}/status', [ServiceController::class, 'updateStatus']);

            Route::get('dashboard', [DashboardController::class, 'show']);
        });

        Route::middleware('role:cliente')->prefix('client')->group(function (): void {
            Route::post('service-requests', [ServiceRequestController::class, 'store']);
            Route::post('service-requests/{serviceRequest}/close', [ServiceRequestController::class, 'close']);

            Route::post('reviews', [ReviewController::class, 'store']);

            Route::get('favorites', [FavoriteController::class, 'index']);
            Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
        });
    });
});
