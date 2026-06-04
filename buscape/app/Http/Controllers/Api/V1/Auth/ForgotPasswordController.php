<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

final class ForgotPasswordController extends Controller
{
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        $status = Password::broker('users')->sendResetLink(['email' => $email]);

        $message = 'Si el correo está registrado en Chamba, te enviamos un enlace para restablecer la contraseña.';

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => 'Espera unos minutos antes de volver a solicitar el enlace.',
            ], 429);
        }

        return response()->json(['message' => $message]);
    }
}
