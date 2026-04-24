<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'chamba.landing')->name('chamba.landing');
Route::view('/app', 'chamba.app')->name('chamba.app');
