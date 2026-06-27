<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CategorySuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategorySuggestionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Debes iniciar sesión para sugerir una categoría.',
            ], 401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $name = trim($data['name']);
        $note = isset($data['note']) ? trim((string) $data['note']) : null;

        $row = CategorySuggestion::query()->create([
            'user_id' => (int) $user->id,
            'name' => $name,
            'note' => $note !== '' ? $note : null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Gracias. Revisaremos tu sugerencia de categoría.',
            'data' => [
                'id' => $row->id,
                'name' => $row->name,
            ],
        ], 201);
    }
}
