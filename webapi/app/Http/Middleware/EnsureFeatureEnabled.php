<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $key = str_contains($feature, '.') ? $feature : "features.{$feature}";
        $default = $feature === 'escrow' ? false : true;

        if (! (bool) chamba_setting($key, $default)) {
            return response()->json([
                'message' => 'Esta función no está disponible en Busca PE.',
                'code' => 'feature_disabled',
            ], 404);
        }

        return $next($request);
    }
}
