<?php

use Illuminate\Support\Facades\Route;

/** Portada inicial (visitantes): buscador, categorías, CTA → /app */
Route::view('/', 'chamba.home')->name('chamba.home');

/** Redirección desde la portada anterior */
Route::redirect('/portada', '/', 302);

/** App web Chamba (misma API): acceso / sesión tras la portada */
Route::view('/app', 'chamba.app')->name('chamba.app');
