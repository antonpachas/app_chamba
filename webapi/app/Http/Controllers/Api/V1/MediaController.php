<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sirve archivos almacenados en el FTP a través de Laravel.
 *
 * Política:
 *   - avatars/*  → público (perfil visible para todos).
 *   - services/* → público (catálogo de servicios).
 *   - payments/* → URL firmada con expiración (middleware `signed` en la ruta).
 *                  El backend solo emite URLs firmadas para usuarios autorizados;
 *                  esto permite que <img src="..."> funcione sin Bearer token.
 */
final class MediaController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function show(Request $request, string $name, ?string $folder = null): Response
    {
        // El `folder` viene del defaults() de la ruta. En algunos entornos (Laravel 11
        // + ControllerDispatcher) la inyección por nombre del default no se aplica
        // al argumento si el orden no coincide con la URL, así que leemos del Request
        // como fallback.
        $folder = $folder
            ?: (string) ($request->route()?->defaults['folder'] ?? '')
            ?: (string) ($request->route()?->parameter('folder') ?? '');

        if (! in_array($folder, ['avatars', 'services', 'payments', 'covers', 'ads'], true)) {
            abort(404);
        }

        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            abort(400, 'Nombre inválido.');
        }

        $path = "{$folder}/{$name}";

        $cacheKey = "media:{$path}";
        $cached = Cache::get($cacheKey);
        if (! $cached) {
            $cached = $this->media->read($path);
            if (! $cached) abort(404);
            Cache::put($cacheKey, $cached, now()->addMinutes(30));
        }

        return response($cached['contents'], 200, [
            'Content-Type' => $cached['mime'],
            'Cache-Control' => $folder === 'payments' ? 'private, max-age=300' : 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
