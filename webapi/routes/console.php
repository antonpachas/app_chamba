<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas diarias de Chamba
Schedule::command('chamba:subscriptions:expire')->dailyAt('03:00');
Schedule::command('chamba:escrow:auto-release')->dailyAt('03:30');
