<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Review;
use App\Services\ListingLifecycleService;
use App\Services\ListingPresenterService;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;

final class PublicProviderController extends Controller
{
    public function __construct(
        private readonly ListingLifecycleService $listings,
        private readonly ListingPresenterService $presenter,
    ) {}

    public function show(int $providerProfile): JsonResponse
    {
        if (! (bool) chamba_setting('providers.public_profile_enabled', true)) {
            return response()->json([
                'message' => 'Los perfiles públicos de negocios no están disponibles.',
            ], 404);
        }

        $p = ProviderProfile::query()
            ->with([
                'user:id,full_name,phone,avatar_path,status',
                'district.province.department',
            ])
            ->findOrFail($providerProfile);

        if ((string) ($p->user?->status ?? '') !== 'activo') {
            return response()->json(['message' => 'Este negocio no está disponible.'], 404);
        }

        $showContact = (bool) chamba_setting('providers.show_contact_on_public_profile', true);
        $media = app(MediaStorageService::class);
        $isPro = $this->presenter->resolveIsPro((int) ($p->user_id ?? 0));

        $listings = ProviderService::query()
            ->with(['category', 'images', 'providerProfile.user', 'providerProfile.district.province.department'])
            ->where('provider_profile_id', $p->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (ProviderService $s) => $this->listings->isVisible($s))
            ->values();

        $listingRows = $listings->map(function (ProviderService $s) use ($isPro) {
            $row = $this->presenter->toSearchRow($s, $isPro);
            $row['service_id'] = $s->id;

            return $row;
        });

        $reviews = Review::query()
            ->where('provider_profile_id', $p->id)
            ->with('client:id,full_name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at,
                'client_name' => $r->client?->full_name,
            ]);

        return response()->json([
            'data' => [
                'id' => $p->id,
                'name' => $p->business_name ?: $p->user?->full_name,
                'description' => $p->description,
                'avg_rating' => $p->avg_rating,
                'total_reviews' => $p->total_reviews,
                'is_verified' => (bool) $p->is_verified,
                'is_pro' => $isPro,
                'avatar_url' => $media->publicUrl($p->user?->avatar_path),
                'whatsapp' => $showContact ? $p->whatsapp : null,
                'contact_phone' => $showContact ? $p->contact_phone : null,
                'address_text' => $p->address_text,
                'district_name' => $p->district?->name,
                'province_name' => $p->district?->province?->name,
                'department_name' => $p->district?->province?->department?->name,
                'listings' => $listingRows,
                'listings_count' => $listingRows->count(),
                'reviews' => $reviews,
            ],
        ]);
    }
}
