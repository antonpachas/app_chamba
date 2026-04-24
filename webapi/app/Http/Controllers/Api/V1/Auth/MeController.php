<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('providerProfile');

        return response()->json([
            'user' => UserResource::make($user),
        ]);
    }
}
