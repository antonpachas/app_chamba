<?php

use App\Http\Controllers\SetupController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

// Bootstrap remoto para hosting sin terminal (protegido por token en .env)
Route::get('/setup', SetupController::class);

// Sitemap y robots
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('chamba.sitemap');
Route::get('/robots.txt', function () {
    return response(
        "User-agent: *\nAllow: /\nSitemap: " . url('/sitemap.xml') . "\n",
        200,
        ['Content-Type' => 'text/plain']
    );
});

// Google OAuth — el callback redirige al SPA con el token
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Web App Manifest (PWA)
Route::get('/site.webmanifest', function () {
    return response()->json([
        'name'             => config('app.name'),
        'short_name'       => 'BuscaPE',
        'description'      => 'Encuentra negocios y profesionales cerca de ti.',
        'start_url'        => '/',
        'scope'            => '/',
        'display'          => 'standalone',
        'orientation'      => 'portrait',
        'background_color' => '#FFFFFF',
        'theme_color'      => '#003874',
        'lang'             => 'es-PE',
        'icons'            => [
            ['src' => asset('img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('chamba.manifest');

// SPA Vue — sirve el mismo HTML para cualquier ruta que no sea API
// El vue-router se encarga de renderizar la vista correcta en el cliente
Route::view('/{any?}', 'chamba.app')
    ->where('any', '^(?!api|setup|sitemap\.xml|robots\.txt|site\.webmanifest|auth/google).*')
    ->name('chamba.app');
