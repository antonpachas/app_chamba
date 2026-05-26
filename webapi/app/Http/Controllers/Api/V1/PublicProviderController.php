<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;

final class PublicProviderController extends Controller
{
    public function show(int $providerProfile): JsonResponse
    {
        $p = ProviderProfile::query()
            ->with([
                'user:id,full_name,phone,avatar_path',
                'district.province.department',
                'providerServices' => fn ($q) => $q->where('is_active', 1),
                'providerServices.category:id,name',
                'providerServices.images',
            ])
            ->findOrFail($providerProfile);

        $media = app(MediaStorageService::class);

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
                'avatar_url' => $media->publicUrl($p->user?->avatar_path),
                'whatsapp' => $p->whatsapp,
                'contact_phone' => $p->contact_phone,
                'address_text' => $p->address_text,
                'district' => $p->district?->only(['id', 'name']),
                'province' => $p->district?->province?->only(['id', 'name']),
                'department' => $p->district?->province?->department?->only(['id', 'name']),
                'services' => $p->providerServices->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'description' => $s->description,
                    'base_price' => $s->base_price,
                    'price_type' => $s->price_type,
                    'category' => $s->category?->only(['id', 'name']),
                    'images' => $s->images->map(fn ($i) => [
                        'id' => $i->id,
                        'url' => $media->publicUrl($i->path),
                    ])->values(),
                    'cover_image_url' => $s->images->isNotEmpty() ? $media->publicUrl($s->images->first()->path) : null,
                ]),
                'reviews' => $reviews,
            ],
        ]);
    }
}
