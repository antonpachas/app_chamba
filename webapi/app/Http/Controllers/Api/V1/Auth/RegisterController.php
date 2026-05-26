<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\StoredProcedureService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

final class RegisterController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $passwordHash = Hash::make($data['password']);

        $userId = $this->storedProcedures->registerUser(
            $data['full_name'],
            $data['email'],
            $data['phone'] ?? null,
            $passwordHash,
            $data['role'],
        );

        $user = User::query()->with('providerProfile')->findOrFail($userId);

        if ($user->role === 'proveedor') {
            $this->subscriptions->startProviderTrial($user);
        } else {
            $this->subscriptions->ensureSubscription($user);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ], 201);
    }
}
