<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PublicPlatformController extends Controller
{
    public function config(): JsonResponse
    {
        return response()->json([
            'data' => [
                'provider_public_profile' => (bool) chamba_setting('providers.public_profile_enabled', true),
                'provider_show_contact' => (bool) chamba_setting('providers.show_contact_on_public_profile', true),
            ],
        ]);
    }
}
