<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ServiceQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QuoteController extends Controller
{
    public function decide(Request $request, int $quote): JsonResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:aceptar,rechazar',
        ]);

        $q = ServiceQuote::query()
            ->whereHas('serviceRequest', fn ($r) => $r->where('client_user_id', $request->user()->id))
            ->findOrFail($quote);

        if ($q->status !== 'pendiente') {
            return response()->json(['message' => 'Esta cotización ya fue respondida.'], 422);
        }

        if ($data['decision'] === 'aceptar') {
            $q->update(['status' => 'aceptada']);
            $q->serviceRequest()->update(['status' => 'aceptado']);
            ServiceQuote::query()
                ->where('service_request_id', $q->service_request_id)
                ->where('id', '!=', $q->id)
                ->where('status', 'pendiente')
                ->update(['status' => 'rechazada']);
        } else {
            $q->update(['status' => 'rechazada']);
        }

        return response()->json(['data' => ['id' => $q->id, 'status' => $q->status]]);
    }
}
