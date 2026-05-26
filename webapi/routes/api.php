<?php

use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\PaymentAdminController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionAdminController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionPlanAdminController;
use App\Http\Controllers\Api\V1\Admin\SystemSettingsAdminController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\AvatarController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\Client\FavoriteController;
use App\Http\Controllers\Api\V1\Client\PaymentController as ClientPaymentController;
use App\Http\Controllers\Api\V1\Client\QuoteController as ClientQuoteController;
use App\Http\Controllers\Api\V1\Client\ReviewController;
use App\Http\Controllers\Api\V1\Client\ServiceRequestController;
use App\Http\Controllers\Api\V1\Client\ServiceRequestListController as ClientServiceRequestListController;
use App\Http\Controllers\Api\V1\GeoController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\Provider\DashboardController;
use App\Http\Controllers\Api\V1\Provider\ProfileController;
use App\Http\Controllers\Api\V1\Provider\QuoteController as ProviderQuoteController;
use App\Http\Controllers\Api\V1\Provider\ServiceController;
use App\Http\Controllers\Api\V1\Provider\ServiceImageController;
use App\Http\Controllers\Api\V1\Provider\ServiceRequestListController as ProviderServiceRequestListController;
use App\Http\Controllers\Api\V1\Provider\WalletController;
use App\Http\Controllers\Api\V1\PublicProviderController;
use App\Http\Controllers\Api\V1\ServiceSearchController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [RegisterController::class, 'store']);
    Route::post('auth/login', [LoginController::class, 'store']);
    Route::post('auth/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1');
    Route::post('auth/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:10,1');

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('geo/departments', [GeoController::class, 'departments']);
    Route::get('geo/provinces', [GeoController::class, 'provinces']);
    Route::get('geo/districts', [GeoController::class, 'districts']);
    Route::get('services/search', [ServiceSearchController::class, 'index']);
    Route::get('providers/{providerProfile}', [PublicProviderController::class, 'show'])->whereNumber('providerProfile');
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);

    Route::get('media/avatars/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'avatars')
        ->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/services/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'services')
        ->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/payments/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'payments')
        ->middleware('auth:sanctum')
        ->where('name', '[A-Za-z0-9_.-]+');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [LogoutController::class, 'store']);
        Route::get('auth/me', [MeController::class, 'show']);

        Route::get('subscriptions/me', [SubscriptionController::class, 'me']);
        Route::post('subscriptions/pay', [SubscriptionController::class, 'pay']);
        Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel']);

        Route::post('me/avatar', [AvatarController::class, 'store']);
        Route::delete('me/avatar', [AvatarController::class, 'destroy']);

        Route::middleware('role:proveedor')->prefix('provider')->group(function (): void {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::post('profile', [ProfileController::class, 'store']);
            Route::put('profile', [ProfileController::class, 'update']);

            Route::get('services', [ServiceController::class, 'index']);
            Route::post('services', [ServiceController::class, 'store']);
            Route::put('services/{service}', [ServiceController::class, 'update']);
            Route::patch('services/{service}/status', [ServiceController::class, 'updateStatus']);

            Route::post('services/{service}/images', [ServiceImageController::class, 'store']);
            Route::delete('services/{service}/images/{image}', [ServiceImageController::class, 'destroy']);

            Route::get('dashboard', [DashboardController::class, 'show']);

            Route::get('service-requests', [ProviderServiceRequestListController::class, 'index']);
            Route::patch('service-requests/{serviceRequest}/status', [ProviderServiceRequestListController::class, 'updateStatus']);

            Route::post('quotes', [ProviderQuoteController::class, 'store']);

            Route::get('wallet', [WalletController::class, 'show']);
            Route::patch('wallet', [WalletController::class, 'update']);
            Route::post('wallet/withdrawals', [WalletController::class, 'requestWithdrawal']);
        });

        Route::middleware('role:cliente')->prefix('client')->group(function (): void {
            Route::get('service-requests', [ClientServiceRequestListController::class, 'index']);
            Route::post('service-requests', [ServiceRequestController::class, 'store']);
            Route::post('service-requests/{serviceRequest}/close', [ServiceRequestController::class, 'close']);

            Route::patch('quotes/{quote}', [ClientQuoteController::class, 'decide']);

            Route::get('payments', [ClientPaymentController::class, 'index']);
            Route::post('payments', [ClientPaymentController::class, 'store']);
            Route::post('payments/{payment}/confirm-completed', [ClientPaymentController::class, 'confirmCompleted']);

            Route::post('reviews', [ReviewController::class, 'store']);

            Route::get('favorites', [FavoriteController::class, 'index']);
            Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::get('dashboard', [AdminDashboardController::class, 'show']);
            Route::get('payments', [PaymentAdminController::class, 'index']);
            Route::post('payments/{payment}/confirm', [PaymentAdminController::class, 'confirm']);
            Route::post('payments/{payment}/reject', [PaymentAdminController::class, 'reject']);
            Route::get('withdrawals', [PaymentAdminController::class, 'withdrawals']);
            Route::post('withdrawals/{withdrawal}/pay', [PaymentAdminController::class, 'payWithdrawal']);

            Route::get('subscriptions/payments', [SubscriptionAdminController::class, 'payments']);
            Route::post('subscriptions/payments/{payment}/confirm', [SubscriptionAdminController::class, 'confirm']);
            Route::post('subscriptions/payments/{payment}/reject', [SubscriptionAdminController::class, 'reject']);
            Route::get('subscriptions', [SubscriptionAdminController::class, 'subscriptions']);

            Route::get('settings', [SystemSettingsAdminController::class, 'index']);
            Route::put('settings/{key}', [SystemSettingsAdminController::class, 'update'])->where('key', '[A-Za-z0-9_.\-]+');
            Route::put('settings', [SystemSettingsAdminController::class, 'bulkUpdate']);
            Route::get('settings-logs', [SystemSettingsAdminController::class, 'logs']);

            Route::get('plans', [SubscriptionPlanAdminController::class, 'index']);
            Route::put('plans/{plan}', [SubscriptionPlanAdminController::class, 'update']);
            Route::get('plans/{plan}/logs', [SubscriptionPlanAdminController::class, 'logs']);
        });
    });
});
