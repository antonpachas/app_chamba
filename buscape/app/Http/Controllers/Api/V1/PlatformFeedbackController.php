<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlatformFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformFeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        PlatformFeedback::create([
            'user_id' => $request->user()?->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'Gracias por tu opinión sobre Busca PE.'], 201);
    }
}
