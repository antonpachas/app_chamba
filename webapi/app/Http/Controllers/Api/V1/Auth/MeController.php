<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdateMeRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class MeController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('providerProfile');

        $entitlements = [
            'shows_ads' => $this->subscriptions->userShowsAds($user),
        ];

        if ($user->role === 'cliente') {
            $entitlements['requests_this_month'] = $this->subscriptions->clientRequestsThisMonth($user);
            $entitlements['can_create_request'] = $this->subscriptions->clientCanCreateRequest($user);
        }

        if ($user->role === 'proveedor') {
            $entitlements['requests_received_this_month'] = $this->subscriptions->providerRequestsReceivedThisMonth($user);
            $entitlements['can_receive_request'] = $this->subscriptions->providerCanReceiveRequest($user);
        }

        return response()->json([
            'user' => UserResource::make($user),
            'entitlements' => $entitlements,
        ]);
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'], $user->password_hash)) {
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual no es correcta.'],
                ]);
            }
            $user->password_hash = Hash::make($data['password']);
        }

        $user->full_name = $data['full_name'];
        $user->phone = $data['phone'] ?? null;
        $user->save();

        $user->load('providerProfile');

        return response()->json([
            'message' => 'Perfil actualizado.',
            'user' => UserResource::make($user),
        ]);
    }
}
