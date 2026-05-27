<?php

use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\LedgerAdminController;
use App\Http\Controllers\Api\V1\Admin\PaymentAdminController;
use App\Http\Controllers\Api\V1\Admin\PlatformAdAdminController;
use App\Http\Controllers\Api\V1\Admin\ReportsAdminController;
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
use App\Http\Controllers\Api\V1\ListingShowController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\Client\HistoryController as ClientHistoryController;
use App\Http\Controllers\Api\V1\Provider\DashboardController;
use App\Http\Controllers\Api\V1\Provider\LocationController as ProviderLocationController;
use App\Http\Controllers\Api\V1\Provider\NotificationController as ProviderNotificationController;
use App\Http\Controllers\Api\V1\Provider\ProfileController;
use App\Http\Controllers\Api\V1\Provider\QuoteController as ProviderQuoteController;
use App\Http\Controllers\Api\V1\Provider\ServiceController;
use App\Http\Controllers\Api\V1\Provider\ServiceImageController;
use App\Http\Controllers\Api\V1\Provider\ServiceRequestDeliveryController as ProviderRequestDeliveryController;
use App\Http\Controllers\Api\V1\Provider\ServiceRequestListController as ProviderServiceRequestListController;
use App\Http\Controllers\Api\V1\Provider\WalletController;
use App\Http\Controllers\Api\V1\PlatformFeedbackController;
use App\Http\Controllers\Api\V1\PublicAdsController;
use App\Http\Controllers\Api\V1\PublicPlatformController;
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
    Route::get('geo/ubigeo', [GeoController::class, 'resolveUbigeo']);
    Route::get('listings/search', [ServiceSearchController::class, 'index']);
    Route::get('services/search', [ServiceSearchController::class, 'index']);
    Route::get('listings/{listing}', [ListingShowController::class, 'show'])->whereNumber('listing');
    Route::get('services/{listing}', [ListingShowController::class, 'show'])->whereNumber('listing');
    Route::get('ads/config', [PublicAdsController::class, 'config']);
    Route::get('ads/banners', [PublicAdsController::class, 'banners']);
    Route::post('ads/banners/{ad}/click', [PublicAdsController::class, 'click'])->whereNumber('ad');
    Route::post('feedback', [PlatformFeedbackController::class, 'store']);
    Route::get('platform/config', [PublicPlatformController::class, 'config']);
    Route::get('providers/{providerProfile}', [PublicProviderController::class, 'show'])->whereNumber('providerProfile');
    Route::get('subscriptions/plans', [SubscriptionController::class, 'plans']);

    Route::get('media/avatars/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'avatars')
        ->name('media.avatars')
        ->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/services/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'services')
        ->name('media.services')
        ->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/ads/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'ads')
        ->name('media.ads')
        ->where('name', '[A-Za-z0-9_.-]+');
    // payments: el backend solo genera URLs firmadas (TemporarySignedRoute) para
    // usuarios autorizados. La firma incluye expiración (24h) y reemplaza la
    // necesidad de Bearer token: así <img src="..."> funciona en el navegador.
    Route::get('media/payments/{name}', [MediaController::class, 'show'])
        ->defaults('folder', 'payments')
        ->name('media.payments')
        ->middleware('signed')
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

            $registerListings = static function (string $prefix) {
                Route::get($prefix, [ServiceController::class, 'index']);
                Route::post($prefix, [ServiceController::class, 'store']);
                Route::put("{$prefix}/{service}", [ServiceController::class, 'update']);
                Route::patch("{$prefix}/{service}/status", [ServiceController::class, 'updateStatus']);
                Route::post("{$prefix}/{service}/renew", [ServiceController::class, 'renew']);
                Route::post("{$prefix}/{service}/images", [ServiceImageController::class, 'store']);
                Route::delete("{$prefix}/{service}/images/{image}", [ServiceImageController::class, 'destroy']);
            };
            $registerListings('listings');
            $registerListings('services');

            Route::get('dashboard', [DashboardController::class, 'show']);

            Route::get('notifications', [ProviderNotificationController::class, 'index']);
            Route::post('notifications/read-all', [ProviderNotificationController::class, 'markAllRead']);
            Route::post('notifications/{notification}/read', [ProviderNotificationController::class, 'markRead'])->whereNumber('notification');

            Route::get('service-requests', [ProviderServiceRequestListController::class, 'index']);
            Route::patch('service-requests/{serviceRequest}/status', [ProviderServiceRequestListController::class, 'updateStatus']);

            Route::middleware('feature:escrow')->group(function (): void {
                Route::post('service-requests/{serviceRequest}/evidence', [ProviderRequestDeliveryController::class, 'uploadEvidence']);
                Route::delete('service-requests/{serviceRequest}/evidence/{evidence}', [ProviderRequestDeliveryController::class, 'deleteEvidence']);
                Route::post('service-requests/{serviceRequest}/deliver', [ProviderRequestDeliveryController::class, 'markDelivered']);
                Route::post('quotes', [ProviderQuoteController::class, 'store']);
                Route::get('wallet', [WalletController::class, 'show']);
                Route::patch('wallet', [WalletController::class, 'update']);
                Route::post('wallet/withdrawals', [WalletController::class, 'requestWithdrawal']);
            });

            // Sedes / direcciones del proveedor
            Route::get('locations', [ProviderLocationController::class, 'index']);
            Route::post('locations', [ProviderLocationController::class, 'store']);
            Route::put('locations/{location}', [ProviderLocationController::class, 'update']);
            Route::delete('locations/{location}', [ProviderLocationController::class, 'destroy']);

        });

        Route::middleware('role:cliente')->prefix('client')->group(function (): void {
            Route::get('service-requests', [ClientServiceRequestListController::class, 'index']);
            Route::post('service-requests', [ServiceRequestController::class, 'store']);
            Route::post('service-requests/{serviceRequest}/close', [ServiceRequestController::class, 'close']);

            Route::middleware('feature:escrow')->group(function (): void {
                Route::patch('quotes/{quote}', [ClientQuoteController::class, 'decide']);
                Route::get('payments', [ClientPaymentController::class, 'index']);
                Route::post('payments', [ClientPaymentController::class, 'store']);
                Route::post('payments/{payment}/confirm-completed', [ClientPaymentController::class, 'confirmCompleted']);
                Route::post('payments/{payment}/dispute', [ClientPaymentController::class, 'dispute']);
                Route::get('history', [ClientHistoryController::class, 'index']);
            });

            Route::post('reviews', [ReviewController::class, 'store']);

            Route::get('favorites', [FavoriteController::class, 'index']);
            Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::get('dashboard', [AdminDashboardController::class, 'show']);
            Route::middleware('feature:escrow')->group(function (): void {
                Route::get('payments', [PaymentAdminController::class, 'index']);
                Route::post('payments/{payment}/confirm', [PaymentAdminController::class, 'confirm']);
                Route::post('payments/{payment}/reject', [PaymentAdminController::class, 'reject']);
                Route::get('withdrawals', [PaymentAdminController::class, 'withdrawals']);
                Route::post('withdrawals/{withdrawal}/pay', [PaymentAdminController::class, 'payWithdrawal']);
            });

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

            Route::get('reports/top-categories', [ReportsAdminController::class, 'topCategories']);
            Route::get('reports/top-queries', [ReportsAdminController::class, 'topQueries']);
            Route::get('ledger', [LedgerAdminController::class, 'index']);
            Route::post('ledger/expenses', [LedgerAdminController::class, 'storeExpense']);
            Route::get('platform-ads', [PlatformAdAdminController::class, 'index']);
            Route::post('platform-ads', [PlatformAdAdminController::class, 'store']);
            Route::put('platform-ads/{ad}', [PlatformAdAdminController::class, 'update'])->whereNumber('ad');
            Route::delete('platform-ads/{ad}', [PlatformAdAdminController::class, 'destroy'])->whereNumber('ad');
        });
    });
});
