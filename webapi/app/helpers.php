<?php

use App\Services\SystemSettingsService;

if (! function_exists('chamba_setting')) {
    /**
     * Lee un valor de la tabla `system_settings` (cacheado). Si no existe, regresa $default.
     */
    function chamba_setting(string $key, mixed $default = null): mixed
    {
        try {
            return app(SystemSettingsService::class)->get($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
