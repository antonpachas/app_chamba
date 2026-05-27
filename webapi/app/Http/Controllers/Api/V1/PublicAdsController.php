<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlatformAd;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicAdsController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'adsense' => [
                'enabled' => (bool) chamba_setting('ads.adsense_enabled', false),
                'client_id' => (string) chamba_setting('ads.adsense_client_id', ''),
                'slots' => [
                    'home' => (string) chamba_setting('ads.adsense_slot_home', ''),
                    'search' => (string) chamba_setting('ads.adsense_slot_search', ''),
                    'detail' => (string) chamba_setting('ads.adsense_slot_detail', ''),
                ],
            ],
            'custom' => [
                'enabled' => (bool) chamba_setting('ads.custom_enabled', true),
                'priority' => (string) chamba_setting('ads.custom_priority', 'custom_first'),
            ],
        ]);
    }

    public function banners(Request $request): JsonResponse
    {
        $placement = (string) $request->query('placement', 'home');
        $rows = PlatformAd::activeForPlacement($placement)->limit(5)->get();

        foreach ($rows as $ad) {
            $ad->increment('impressions');
        }

        return response()->json([
            'data' => $rows->map(fn (PlatformAd $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'image_url' => $this->media->publicUrl($a->image_path),
                'link_url' => $a->link_url,
            ]),
        ]);
    }

    public function click(int $ad): JsonResponse
    {
        PlatformAd::query()->where('id', $ad)->increment('clicks');

        return response()->json(['ok' => true]);
    }
}
