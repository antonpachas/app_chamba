<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestListController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function index(Request $request): JsonResponse
    {
        $rows = ServiceRequest::query()
            ->where('client_user_id', $request->user()->id)
            ->with([
                'providerService:id,provider_profile_id,title,base_price,price_type,category_id',
                'providerService.category:id,name',
                'providerService.providerProfile:id,user_id,business_name,whatsapp,contact_phone,avg_rating,total_reviews',
                'providerService.providerProfile.user:id,full_name',
            ])
            ->withCount(['quotes' => fn ($q) => $q, 'messages'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $rows->loadMissing('quotes');
        $rows->loadMissing('payment');
        $rows->loadMissing('evidence');
        $rows->loadMissing('events');
        $rows->loadMissing('review:id,service_request_id,rating,comment,created_at');

        return response()->json([
            'data' => $rows->map(function (ServiceRequest $r) {
                $svc = $r->providerService;
                $prof = $svc?->providerProfile;
                $latestQuote = $r->quotes->sortByDesc('id')->first();
                return [
                    'id' => $r->id,
                    'status' => $r->status,
                    'message' => $r->message,
                    'contact_channel' => $r->contact_channel,
                    'messages_count' => (int) ($r->messages_count ?? 0),
                    'can_review' => $r->review === null,
                    'review' => $r->review ? [
                        'rating' => (int) $r->review->rating,
                        'comment' => $r->review->comment,
                        'created_at' => $r->review->created_at,
                    ] : null,
                    'created_at' => $r->created_at,
                    'delivered_at' => $r->delivered_at,
                    'auto_release_at' => $r->auto_release_at,
                    'disputed_at' => $r->disputed_at,
                    'service' => $svc ? [
                        'id' => $svc->id,
                        'title' => $svc->title,
                        'price_type' => $svc->price_type,
                        'base_price' => $svc->base_price,
                        'category' => $svc->category?->only(['id', 'name']),
                    ] : null,
                    'provider' => $prof ? [
                        'id' => $prof->id,
                        'name' => $prof->business_name ?: $prof->user?->full_name,
                        'avg_rating' => $prof->avg_rating,
                        'total_reviews' => $prof->total_reviews,
                        'whatsapp' => $prof->whatsapp,
                        'contact_phone' => $prof->contact_phone,
                    ] : null,
                    'latest_quote' => $latestQuote?->only(['id', 'amount', 'currency', 'estimated_days', 'notes', 'status', 'created_at']),
                    'payment' => $r->payment ? array_merge(
                        $r->payment->only(['id', 'status', 'amount', 'commission_amount', 'net_amount', 'payment_method', 'payment_reference', 'paid_at', 'confirmed_at', 'released_at']),
                        ['proof_image_url' => $this->media->publicUrl($r->payment->proof_image_path)],
                    ) : null,
                    'evidence' => $r->evidence->map(fn ($e) => [
                        'id' => $e->id,
                        'url' => $this->media->publicUrl($e->path),
                        'caption' => $e->caption,
                        'sort_order' => $e->sort_order,
                        'created_at' => $e->created_at,
                    ])->values(),
                    'timeline' => $r->events->map(fn ($e) => [
                        'id' => $e->id,
                        'from_status' => $e->from_status,
                        'to_status' => $e->to_status,
                        'actor_role' => $e->actor_role,
                        'note' => $e->note,
                        'created_at' => $e->created_at,
                    ])->values(),
                ];
            }),
        ]);
    }
}
