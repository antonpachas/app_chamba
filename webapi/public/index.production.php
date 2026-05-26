<?php

/**
 * Front controller para producción (jaapsystem.com/v1/chamba).
 * El código Laravel vive fuera del docroot por seguridad.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Carpeta privada del proyecto Laravel.
$appBase = '/home/jaapsyst/chamba_app';

if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBase.'/vendor/autoload.php';

(require_once $appBase.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
