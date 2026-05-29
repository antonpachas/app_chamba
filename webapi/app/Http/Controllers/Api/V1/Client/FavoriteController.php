<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\ToggleFavoriteRequest;
use App\Models\Favorite;
use App\Models\ProviderService;
use App\Services\ListingPublicIdService;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class FavoriteController extends Controller
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly ListingPublicIdService $publicIds,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'cliente') {
            return response()->json([
                'message' => 'Solo las cuentas de cliente pueden ver favoritos.',
            ], 403);
        }

        $hasServiceFavorite = Schema::hasColumn('favorites', 'provider_service_id');

        if ($hasServiceFavorite) {
            $rows = Favorite::query()
                ->from('favorites as f')
                ->join('provider_services as ps', 'ps.id', '=', 'f.provider_service_id')
                ->join('provider_profiles as pp', 'pp.id', '=', 'ps.provider_profile_id')
                ->join('users as u', 'u.id', '=', 'pp.user_id')
                ->join('districts as d', 'd.id', '=', 'pp.district_id')
                ->join('provinces as p', 'p.id', '=', 'd.province_id')
                ->where('f.user_id', (int) $request->user()->id)
                ->whereNotNull('f.provider_service_id')
                ->orderByDesc('f.created_at')
                ->get([
                    'f.id as favorite_id',
                    'f.created_at',
                    'f.provider_service_id',
                    'ps.provider_profile_id',
                    'ps.title',
                    'ps.base_price',
                    'ps.price_type',
                    DB::raw('COALESCE(pp.business_name, u.full_name) as provider_name'),
                    'pp.whatsapp',
                    'pp.contact_phone',
                    'pp.avg_rating',
                    'pp.total_reviews',
                    'd.name as district_name',
                    'p.name as province_name',
                ]);
        } else {
            $firstServiceByProfile = DB::table('provider_services')
                ->selectRaw('provider_profile_id, MIN(id) as provider_service_id')
                ->groupBy('provider_profile_id');

            $rows = Favorite::query()
                ->from('favorites as f')
                ->leftJoinSub($firstServiceByProfile, 'ps0', function ($join): void {
                    $join->on('ps0.provider_profile_id', '=', 'f.provider_profile_id');
                })
                ->leftJoin('provider_services as ps', 'ps.id', '=', 'ps0.provider_service_id')
                ->join('provider_profiles as pp', 'pp.id', '=', 'f.provider_profile_id')
                ->join('users as u', 'u.id', '=', 'pp.user_id')
                ->join('districts as d', 'd.id', '=', 'pp.district_id')
                ->join('provinces as p', 'p.id', '=', 'd.province_id')
                ->where('f.user_id', (int) $request->user()->id)
                ->orderByDesc('f.created_at')
                ->get([
                    'f.id as favorite_id',
                    'f.created_at',
                    'ps0.provider_service_id',
                    'pp.id as provider_profile_id',
                    'ps.title',
                    'ps.base_price',
                    'ps.price_type',
                    DB::raw('COALESCE(pp.business_name, u.full_name) as provider_name'),
                    'pp.whatsapp',
                    'pp.contact_phone',
                    'pp.avg_rating',
                    'pp.total_reviews',
                    'd.name as district_name',
                    'p.name as province_name',
                ]);
        }

        $serviceIds = $rows->pluck('provider_service_id')->map(fn ($id) => (int) $id)->unique()->values();
        $coversByService = collect();
        if ($serviceIds->isNotEmpty()) {
            $coversByService = DB::table('service_images')
                ->select(['provider_service_id', 'path'])
                ->whereIn('provider_service_id', $serviceIds)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('provider_service_id')
                ->map(fn ($images) => $images->first()->path ?? null);
        }

        return response()->json([
            'data' => $rows->map(function ($row) use ($coversByService): array {
                $item = (array) $row;
                $sid = (int) ($item['provider_service_id'] ?? 0);
                $coverPath = $coversByService->get($sid);
                $item['cover_image_url'] = $coverPath ? $this->media->publicUrl((string) $coverPath) : null;
                if ($sid > 0) {
                    $item['listing_ref'] = $this->publicIds->encode($sid);
                }
                return $item;
            })->values()->all(),
        ]);
    }

    public function toggle(ToggleFavoriteRequest $request): JsonResponse
    {
        if ($request->user()?->role !== 'cliente') {
            return response()->json([
                'message' => 'Inicia sesión como cliente para guardar favoritos.',
            ], 403);
        }

        $data = $request->validated();
        $userId = (int) $request->user()->id;
        $hasServiceFavorite = Schema::hasColumn('favorites', 'provider_service_id');
        $serviceId = isset($data['provider_service_id']) ? (int) $data['provider_service_id'] : 0;
        $profileId = isset($data['provider_profile_id']) ? (int) $data['provider_profile_id'] : 0;

        if ($serviceId <= 0 && $profileId > 0) {
            $serviceId = (int) ProviderService::query()
                ->where('provider_profile_id', $profileId)
                ->orderBy('id')
                ->value('id');
        }

        $listing = $serviceId > 0 ? ProviderService::query()->whereKey($serviceId)->first() : null;
        if ($listing !== null) {
            $profileId = (int) $listing->provider_profile_id;
        }

        if ($profileId <= 0) {
            return response()->json(['message' => 'Negocio no encontrado para favorito.'], 422);
        }

        $action = 'added';
        if ($hasServiceFavorite && $serviceId > 0) {
            $fav = Favorite::query()
                ->where('user_id', $userId)
                ->where('provider_service_id', $serviceId)
                ->first();

            if ($fav) {
                $fav->delete();
                $action = 'removed';
            } else {
                Favorite::query()
                    ->where('user_id', $userId)
                    ->where('provider_profile_id', $profileId)
                    ->where(function ($q) use ($serviceId): void {
                        $q->whereNull('provider_service_id')
                            ->orWhere('provider_service_id', '!=', $serviceId);
                    })
                    ->delete();

                Favorite::query()->create([
                    'user_id' => $userId,
                    'provider_profile_id' => $profileId,
                    'provider_service_id' => $serviceId,
                ]);
            }
        } else {
            $fav = Favorite::query()
                ->where('user_id', $userId)
                ->where('provider_profile_id', $profileId)
                ->first();

            if ($fav) {
                $fav->delete();
                $action = 'removed';
            } else {
                Favorite::query()->create([
                    'user_id' => $userId,
                    'provider_profile_id' => $profileId,
                ]);
            }
        }

        return response()->json([
            'action' => $action,
        ]);
    }
}
