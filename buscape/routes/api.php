<?php

use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\CategoryAdminController;
use App\Http\Controllers\Api\V1\Admin\GeoAdminController;
use App\Http\Controllers\Api\V1\Admin\ListingAdminController;
use App\Http\Controllers\Api\V1\Admin\ReportsAdminController;
use App\Http\Controllers\Api\V1\Admin\SupportTicketAdminController;
use App\Http\Controllers\Api\V1\Admin\SystemLogAdminController;
use App\Http\Controllers\Api\V1\Admin\SystemSettingsAdminController;
use App\Http\Controllers\Api\V1\Admin\UserAdminController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\AvatarController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\Client\FavoriteController;
use App\Http\Controllers\Api\V1\Client\ReviewController;
use App\Http\Controllers\Api\V1\Client\ServiceRequestController;
use App\Http\Controllers\Api\V1\Client\ServiceRequestListController as ClientServiceRequestListController;
use App\Http\Controllers\Api\V1\GeoController;
use App\Http\Controllers\Api\V1\HomeFeaturedController;
use App\Http\Controllers\Api\V1\ListingShowController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\Provider\DashboardController;
use App\Http\Controllers\Api\V1\Provider\LocationController as ProviderLocationController;
use App\Http\Controllers\Api\V1\Provider\NotificationController as ProviderNotificationController;
use App\Http\Controllers\Api\V1\Provider\ProfileController;
use App\Http\Controllers\Api\V1\Provider\ServiceController;
use App\Http\Controllers\Api\V1\Provider\ServiceImageController;
use App\Http\Controllers\Api\V1\Provider\ServiceRequestDeliveryController as ProviderRequestDeliveryController;
use App\Http\Controllers\Api\V1\Provider\ServiceRequestListController as ProviderServiceRequestListController;
use App\Http\Controllers\Api\V1\PublicPlatformController;
use App\Http\Controllers\Api\V1\PublicProviderController;
use App\Http\Controllers\Api\V1\ServiceRequestMessageController;
use App\Http\Controllers\Api\V1\ServiceSearchController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // -------------------------------------------------------------------------
    // PÚBLICO — sin autenticación
    // -------------------------------------------------------------------------

    // Auth
    Route::post('auth/register', [RegisterController::class, 'store']);
    Route::post('auth/login',    [LoginController::class,    'store']);
    Route::post('auth/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1');
    Route::post('auth/reset-password',  [ResetPasswordController::class,  'store'])->middleware('throttle:10,1');

    // Google OAuth
    Route::get('auth/google',          [SocialAuthController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

    // Catálogo y geo
    Route::get('categories',       [CategoryController::class, 'index']);
    Route::get('geo/departments',  [GeoController::class, 'departments']);
    Route::get('geo/provinces',    [GeoController::class, 'provinces']);
    Route::get('geo/districts',    [GeoController::class, 'districts']);
    Route::get('geo/districts/{district}', [GeoController::class, 'district'])->whereNumber('district');
    Route::get('geo/ubigeo',       [GeoController::class, 'resolveUbigeo']);

    // Búsqueda y listados
    Route::get('listings/search',        [ServiceSearchController::class, 'index']);
    Route::get('listings/home-featured', [HomeFeaturedController::class,  'index']);
    Route::get('listings/{listing}',     [ListingShowController::class,   'show'])->where('listing', '[A-Za-z0-9_-]+');

    // Perfil público de proveedor
    Route::get('providers/{providerProfile}', [PublicProviderController::class, 'show'])->whereNumber('providerProfile');

    // Configuración de la plataforma (nombre, logo, config UI)
    Route::get('platform/config', [PublicPlatformController::class, 'config']);

    // Media pública
    Route::get('media/avatars/{name}',  [MediaController::class, 'show'])->defaults('folder', 'avatars') ->name('media.avatars') ->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/services/{name}', [MediaController::class, 'show'])->defaults('folder', 'services')->name('media.services')->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/covers/{name}',   [MediaController::class, 'show'])->defaults('folder', 'covers')  ->name('media.covers') ->where('name', '[A-Za-z0-9_.-]+');
    Route::get('media/support/{name}',  [MediaController::class, 'show'])->defaults('folder', 'support') ->name('media.support')->middleware('signed')->where('name', '[A-Za-z0-9_.-]+');

    // -------------------------------------------------------------------------
    // AUTENTICADO — cualquier rol
    // -------------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'user.active'])->group(function (): void {

        // Auth
        Route::post('auth/logout', [LogoutController::class, 'store']);
        Route::get('auth/me',      [MeController::class,     'show']);
        Route::put('me',           [MeController::class,     'update']);
        Route::post('me/avatar',   [AvatarController::class, 'store']);
        Route::delete('me/avatar', [AvatarController::class, 'destroy']);

        // Notificaciones del usuario
        Route::get('me/notifications',                       [UserNotificationController::class, 'index']);
        Route::post('me/notifications/read-all',             [UserNotificationController::class, 'markAllRead']);
        Route::post('me/notifications/{notification}/read',  [UserNotificationController::class, 'markRead'])->whereNumber('notification');

        // Mensajes de solicitudes (compartido cliente/proveedor)
        Route::get('service-requests/{serviceRequest}/messages',  [ServiceRequestMessageController::class, 'index']) ->whereNumber('serviceRequest');
        Route::post('service-requests/{serviceRequest}/messages', [ServiceRequestMessageController::class, 'store']) ->whereNumber('serviceRequest');

        // Soporte
        Route::get('support-tickets',                          [SupportTicketController::class, 'index']);
        Route::post('support-tickets',                         [SupportTicketController::class, 'store']);
        Route::get('support-tickets/{ticket}',                 [SupportTicketController::class, 'show'])->whereNumber('ticket');
        Route::post('support-tickets/{ticket}/messages',       [SupportTicketController::class, 'storeMessage'])->whereNumber('ticket');

        // -----------------------------------------------------------------------
        // PROVEEDOR
        // -----------------------------------------------------------------------
        Route::middleware('role:proveedor')->prefix('provider')->group(function (): void {

            // Perfil de negocio
            Route::get('profile',               [ProfileController::class, 'show']);
            Route::post('profile',              [ProfileController::class, 'store']);
            Route::put('profile',               [ProfileController::class, 'update']);
            Route::post('profile/cover',        [ProfileController::class, 'uploadCover']);
            Route::delete('profile/cover',      [ProfileController::class, 'deleteCover']);

            // Anuncios (listings / services — ambas rutas soportadas para compatibilidad)
            $registerListings = static function (string $prefix): void {
                Route::get($prefix,                              [ServiceController::class,      'index']);
                Route::post($prefix,                             [ServiceController::class,      'store']);
                Route::put("{$prefix}/{service}",                [ServiceController::class,      'update']);
                Route::patch("{$prefix}/{service}/status",       [ServiceController::class,      'updateStatus']);
                Route::post("{$prefix}/{service}/renew",         [ServiceController::class,      'renew']);
                Route::post("{$prefix}/{service}/images",        [ServiceImageController::class, 'store']);
                Route::delete("{$prefix}/{service}/images/{image}", [ServiceImageController::class, 'destroy']);
            };
            $registerListings('listings');
            $registerListings('services');

            // Dashboard
            Route::get('dashboard', [DashboardController::class, 'show']);

            // Notificaciones del proveedor
            Route::get('notifications',                      [ProviderNotificationController::class, 'index']);
            Route::post('notifications/read-all',            [ProviderNotificationController::class, 'markAllRead']);
            Route::post('notifications/{notification}/read', [ProviderNotificationController::class, 'markRead'])->whereNumber('notification');

            // Solicitudes recibidas
            Route::get('service-requests',                                [ProviderServiceRequestListController::class, 'index']);
            Route::patch('service-requests/{serviceRequest}/status',      [ProviderServiceRequestListController::class, 'updateStatus']);
            Route::post('service-requests/{serviceRequest}/evidence',     [ProviderRequestDeliveryController::class,    'uploadEvidence']);
            Route::delete('service-requests/{serviceRequest}/evidence/{evidence}', [ProviderRequestDeliveryController::class, 'deleteEvidence']);
            Route::post('service-requests/{serviceRequest}/deliver',      [ProviderRequestDeliveryController::class,    'markDelivered']);

            // Ubicaciones del negocio
            Route::get('locations',              [ProviderLocationController::class, 'index']);
            Route::post('locations',             [ProviderLocationController::class, 'store']);
            Route::put('locations/{location}',   [ProviderLocationController::class, 'update']);
            Route::delete('locations/{location}',[ProviderLocationController::class, 'destroy']);
        });

        // -----------------------------------------------------------------------
        // CLIENTE
        // -----------------------------------------------------------------------
        Route::middleware('role:cliente')->prefix('client')->group(function (): void {

            // Solicitudes enviadas
            Route::get('service-requests',                       [ClientServiceRequestListController::class, 'index']);
            Route::post('service-requests',                      [ServiceRequestController::class,           'store']);
            Route::post('service-requests/{serviceRequest}/close', [ServiceRequestController::class,         'close']);

            // Reseñas
            Route::get('reviews/status', [ReviewController::class, 'status']);
            Route::post('reviews',       [ReviewController::class, 'store']);

            // Favoritos
            Route::get('favorites',        [FavoriteController::class, 'index']);
            Route::post('favorites/toggle',[FavoriteController::class, 'toggle']);
        });

        // -----------------------------------------------------------------------
        // ADMIN
        // -----------------------------------------------------------------------
        Route::middleware('role:admin')->prefix('admin')->group(function (): void {

            Route::get('dashboard', [AdminDashboardController::class, 'show']);

            // Usuarios
            Route::get('users',                    [UserAdminController::class, 'index']);
            Route::get('users/{user}/activity',    [UserAdminController::class, 'activity'])->whereNumber('user');
            Route::post('users/{user}/suspend',    [UserAdminController::class, 'suspend'])->whereNumber('user');
            Route::post('users/{user}/activate',   [UserAdminController::class, 'activate'])->whereNumber('user');
            Route::post('users/{user}/reset-password', [UserAdminController::class, 'resetPassword'])->whereNumber('user');
            Route::get('users/{user}/service-requests/{serviceRequest}/messages',
                [UserAdminController::class, 'requestMessages'])->whereNumber('user')->whereNumber('serviceRequest');

            // Anuncios
            Route::get('listings',                         [ListingAdminController::class, 'index']);
            Route::post('listings/{listing}/hide',         [ListingAdminController::class, 'hide'])->whereNumber('listing');
            Route::post('listings/{listing}/restore',      [ListingAdminController::class, 'restore'])->whereNumber('listing');
            Route::post('listings/{listing}/feature-home', [ListingAdminController::class, 'featureHome'])->whereNumber('listing');
            Route::post('listings/{listing}/unfeature-home', [ListingAdminController::class, 'unfeatureHome'])->whereNumber('listing');
            Route::post('listings/home-featured/reorder', [ListingAdminController::class, 'reorderHomeFeatured']);

            // Categorías
            Route::get('categories',                [CategoryAdminController::class, 'index']);
            Route::post('categories',               [CategoryAdminController::class, 'store']);
            Route::put('categories/{category}',     [CategoryAdminController::class, 'update'])->whereNumber('category');

            // Geo (departamentos, provincias, distritos)
            Route::get('geo/departments',                    [GeoAdminController::class, 'departments']);
            Route::post('geo/departments',                   [GeoAdminController::class, 'storeDepartment']);
            Route::put('geo/departments/{department}',       [GeoAdminController::class, 'updateDepartment'])->whereNumber('department');
            Route::get('geo/provinces',                      [GeoAdminController::class, 'provinces']);
            Route::post('geo/provinces',                     [GeoAdminController::class, 'storeProvince']);
            Route::put('geo/provinces/{province}',           [GeoAdminController::class, 'updateProvince'])->whereNumber('province');
            Route::get('geo/districts',                      [GeoAdminController::class, 'districts']);
            Route::post('geo/districts',                     [GeoAdminController::class, 'storeDistrict']);
            Route::put('geo/districts/{district}',           [GeoAdminController::class, 'updateDistrict'])->whereNumber('district');

            // Configuración del sistema
            Route::get('settings',          [SystemSettingsAdminController::class, 'index']);
            Route::put('settings/{key}',    [SystemSettingsAdminController::class, 'update'])->where('key', '[A-Za-z0-9_.\-]+');
            Route::put('settings',          [SystemSettingsAdminController::class, 'bulkUpdate']);
            Route::get('settings-logs',     [SystemSettingsAdminController::class, 'logs']);

            // Logs del sistema
            Route::get('system-logs',       [SystemLogAdminController::class, 'index']);
            Route::get('system-logs/{log}', [SystemLogAdminController::class, 'show'])->whereNumber('log');

            // Soporte
            Route::get('support-tickets',                      [SupportTicketAdminController::class, 'index']);
            Route::get('support-tickets/{ticket}',             [SupportTicketAdminController::class, 'show'])->whereNumber('ticket');
            Route::patch('support-tickets/{ticket}/status',    [SupportTicketAdminController::class, 'updateStatus'])->whereNumber('ticket');
            Route::post('support-tickets/{ticket}/messages',   [SupportTicketAdminController::class, 'storeMessage'])->whereNumber('ticket');

            // Reportes
            Route::get('reports/top-categories', [ReportsAdminController::class, 'topCategories']);
            Route::get('reports/top-queries',    [ReportsAdminController::class, 'topQueries']);
            Route::get('reports/users',          [ReportsAdminController::class, 'users']);
            Route::get('reports/listings',       [ReportsAdminController::class, 'listings']);
        });
    });
});
