<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('status', 'activo')
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        $this->subscriptions->ensureSubscription($user);

        $user->load('providerProfile');
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ]);
    }
}
