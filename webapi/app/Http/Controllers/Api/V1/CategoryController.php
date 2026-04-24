<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }
}
