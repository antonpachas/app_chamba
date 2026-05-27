<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
