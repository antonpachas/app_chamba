<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user !== null && (string) $user->status !== 'activo') {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Tu cuenta está suspendida. Contacta a soporte si crees que es un error.',
                'code' => 'account_suspended',
            ], 403);
        }

        return $next($request);
    }
}
