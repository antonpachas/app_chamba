<?php

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

/**
 * Endpoint de bootstrap remoto para shared hosting sin terminal.
 * Protegido por token (CHAMBA_SETUP_TOKEN en .env). Inactivo si el token está vacío.
 */
Route::get('/setup', SetupController::class);

/** Redirección desde la portada anterior. */
Route::redirect('/portada', '/app', 302);

/** Sitio raíz: enviar a la app SPA. */
Route::redirect('/', '/app');

/**
 * site.webmanifest dinámico. Sirve el manifest con URLs absolutas que
 * respetan APP_URL (necesario en deploys en subdirectorio: /v1/chamba/...).
 * Si lo dejamos como JSON estático, las rutas `/img/...` y `/app` apuntan
 * al dominio raíz y dan 404 dentro de un subdirectorio.
 */
Route::get('/site.webmanifest', function () {
    return response()->json([
        'name' => 'Busca PE — Servicios locales',
        'short_name' => 'Busca PE',
        'description' => 'Encuentra negocios y profesionales cerca de ti.',
        'start_url' => url('/app'),
        'scope' => url('/app'),
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#003874',
        'theme_color' => '#003874',
        'lang' => 'es-PE',
        'icons' => [
            ['src' => asset('img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('chamba.manifest');

/**
 * SPA Vue. Cualquier sub-ruta dentro de /app la maneja vue-router.
 * Registramos /app y /app/{any} por separado para que también acepte la
 * variante con trailing slash sin depender solo del rewrite de Apache.
 */
Route::view('/app', 'chamba.app')->name('chamba.app');
Route::view('/app/{any}', 'chamba.app')->where('any', '.*');
