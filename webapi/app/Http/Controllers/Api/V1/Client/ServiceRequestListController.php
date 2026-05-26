<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestListController extends Controller
{
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
            ->withCount(['quotes' => fn ($q) => $q])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $rows->loadMissing('quotes');
        $rows->loadMissing('payment');

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
                    'created_at' => $r->created_at,
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
                    'payment' => $r->payment?->only(['id', 'status', 'amount', 'payment_method', 'payment_reference', 'paid_at', 'confirmed_at', 'released_at']),
                ];
            }),
        ]);
    }
}
