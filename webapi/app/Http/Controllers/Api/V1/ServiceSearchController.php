<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceSearchRequest;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;

final class ServiceSearchController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function index(ServiceSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $districtId = isset($data['district_id']) ? (int) $data['district_id'] : null;
        $keyword = isset($data['keyword']) && $data['keyword'] !== '' ? (string) $data['keyword'] : null;
        $userLat = isset($data['user_lat']) ? (float) $data['user_lat'] : null;
        $userLng = isset($data['user_lng']) ? (float) $data['user_lng'] : null;
        $radiusKm = isset($data['radius_km']) ? (float) $data['radius_km'] : null;

        $rows = $this->storedProcedures->searchProviderServices(
            $categoryId,
            $districtId,
            $keyword,
            $userLat,
            $userLng,
            $radiusKm,
        );

        return response()->json([
            'data' => array_map(static fn (object $row): array => (array) $row, $rows),
        ]);
    }
}
