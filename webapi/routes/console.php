<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas diarias Busca PE / Chamba
Schedule::command('chamba:subscriptions:expire')->dailyAt('03:00');
Schedule::command('busca:listings:expire')->dailyAt('02:00')->when(fn () => (bool) chamba_setting('listings.expire_cron_enabled', false));
Schedule::command('chamba:escrow:auto-release')->dailyAt('03:30')->when(fn () => (bool) chamba_setting('features.escrow', false));
