<?php

namespace App\Http\Controllers\Api\V1\Admin\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait PaginatesAdminResources
{
    protected function adminPerPage(Request $request, int $default = 25, int $max = 100): int
    {
        return min($max, max(10, (int) $request->query('per_page', $default)));
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  callable(mixed): array<string, mixed>  $mapper
     * @param  array<string, mixed>  $extraMeta
     */
    protected function adminPaginatedResponse(LengthAwarePaginator $paginator, callable $mapper, array $extraMeta = []): JsonResponse
    {
        return response()->json([
            'data' => collect($paginator->items())->map($mapper)->values()->all(),
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ], $extraMeta),
        ]);
    }
}
