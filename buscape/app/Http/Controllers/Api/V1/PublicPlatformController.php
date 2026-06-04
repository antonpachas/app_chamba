<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PublicPlatformController extends Controller
{
    public function config(): JsonResponse
    {
        $colsSm = max(1, min(2, (int) chamba_setting('ui.search_grid_columns_sm', 1)));
        $colsMd = max(1, min(4, (int) chamba_setting('ui.search_grid_columns_md', 2)));

        return response()->json([
            'data' => [
                'provider_public_profile' => (bool) chamba_setting('providers.public_profile_enabled', true),
                'provider_show_contact' => (bool) chamba_setting('providers.show_contact_on_public_profile', true),
                'search_grid_columns_sm' => $colsSm,
                'search_grid_columns_md' => $colsMd,
            ],
        ]);
    }
}
