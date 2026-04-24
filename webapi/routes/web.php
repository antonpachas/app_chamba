<?php

use Illuminate\Support\Facades\Route;

/** Portada informativa (opcional): …/public/portada */
Route::view('/portada', 'chamba.landing')->name('chamba.landing');

/** App web Chamba (misma API). La raíz redirige aquí para quien entra en …/public/ */
Route::redirect('/', '/app');
Route::view('/app', 'chamba.app')->name('chamba.app');
