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
 * SPA Vue. Cualquier sub-ruta dentro de /app la maneja vue-router.
 * Registramos /app y /app/{any} por separado para que también acepte la
 * variante con trailing slash sin depender solo del rewrite de Apache.
 */
Route::view('/app', 'chamba.app')->name('chamba.app');
Route::view('/app/{any}', 'chamba.app')->where('any', '.*');
