<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class ResetPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $status = Password::broker('users')->reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password_hash' => Hash::make($password),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [$this->humanStatus($status)],
            ]);
        }

        return response()->json([
            'message' => 'Contraseña actualizada. Ya puedes iniciar sesión.',
        ]);
    }

    private function humanStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'El enlace no es válido o expiró. Solicita uno nuevo.',
            Password::INVALID_USER => 'No encontramos una cuenta con ese correo.',
            default => 'No se pudo restablecer la contraseña. Inténtalo de nuevo.',
        };
    }
}
