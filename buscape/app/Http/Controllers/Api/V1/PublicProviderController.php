<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\ProviderVisibilityEvent;
use App\Models\Review;
use App\Services\ListingGuestPreviewService;
use App\Services\ListingLifecycleService;
use App\Services\ListingListFormatter;
use App\Services\ListingPresenterService;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;

final class PublicProviderController extends Controller
{
    public function __construct(
        private readonly ListingLifecycleService $listings,
        private readonly ListingPresenterService $presenter,
        private readonly ListingGuestPreviewService $guestPreview,
        private readonly ListingListFormatter $listFormatter,
    ) {}

    public function show(\Illuminate\Http\Request $request, int $providerProfile): JsonResponse
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

        ProviderVisibilityEvent::query()->create([
            'provider_profile_id' => (int) $p->id,
            'provider_service_id' => null,
            'search_event_id' => null,
            'viewer_user_id' => $request->user('sanctum')?->id,
            'source' => 'public_profile',
            'created_at' => now(),
        ]);

        $isGuest = $request->user('sanctum') === null;
        $hoursSummary = app(\App\Services\BusinessHoursService::class)->summarize($p->business_hours);
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

        $listingRows = $listings->map(function (ProviderService $s) use ($isPro, $isGuest, $p) {
            $row = $this->presenter->toSearchRow($s, $isPro);
            $row['service_id'] = $s->id;
            $row = $this->listFormatter->forList($row, $p->business_hours);
            if ($isGuest) {
                $row = $this->guestPreview->scrubRow($row);
                unset($row['whatsapp'], $row['contact_phone']);
            }

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
                'description' => $isGuest ? $this->guestPreview->truncate($p->description) : $p->description,
                'description_truncated' => $isGuest && $p->description && mb_strlen((string) $p->description) > $this->guestPreview->maxDescriptionChars(),
                'guest_preview' => $isGuest,
                'contact_on_detail_only' => ! empty($p->whatsapp) || ! empty($p->contact_phone),
                'avg_rating' => $p->avg_rating,
                'is_open_now' => $hoursSummary['is_open_now'],
                'hours_summary' => $hoursSummary['hours_summary'],
                'business_hours' => $hoursSummary['schedule'],
                'total_reviews' => $p->total_reviews,
                'is_verified' => (bool) $p->is_verified,
                'is_pro' => $isPro,
                'avatar_url' => $media->publicUrl($p->user?->avatar_path),
                'cover_image_url' => $media->publicUrl($p->cover_path),
                'whatsapp' => null,
                'contact_phone' => null,
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
