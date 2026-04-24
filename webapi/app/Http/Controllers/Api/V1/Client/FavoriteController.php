<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\ToggleFavoriteRequest;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FavoriteController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rows = $this->storedProcedures->listUserFavorites((int) $request->user()->id);

        return response()->json([
            'data' => array_map(static fn (object $row): array => (array) $row, $rows),
        ]);
    }

    public function toggle(ToggleFavoriteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $action = $this->storedProcedures->toggleFavorite(
            (int) $request->user()->id,
            (int) $data['provider_profile_id'],
        );

        return response()->json([
            'action' => $action,
        ]);
    }
}
