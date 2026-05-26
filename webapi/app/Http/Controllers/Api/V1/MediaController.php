<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
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
 *   - payments/* → privado: solo dueño del pago o admin.
 */
final class MediaController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function show(Request $request, string $folder, string $name): Response
    {
        if (! in_array($folder, ['avatars', 'services', 'payments'], true)) {
            abort(404);
        }

        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            abort(400, 'Nombre inválido.');
        }

        $path = "{$folder}/{$name}";

        if ($folder === 'payments') {
            $this->authorizePaymentAccess($request, $path);
        }

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

    private function authorizePaymentAccess(Request $request, string $path): void
    {
        $user = $request->user();
        if (! $user) abort(401);

        if ($user->role === 'admin') return;

        $subOwns = SubscriptionPayment::where('proof_image_path', $path)
            ->where('user_id', $user->id)
            ->exists();

        if ($subOwns) return;

        if (class_exists(\App\Models\ServicePayment::class)) {
            $svcOwns = \App\Models\ServicePayment::where('proof_image_path', $path)
                ->where('client_user_id', $user->id)
                ->exists();
            if ($svcOwns) return;
        }

        abort(403);
    }
}
