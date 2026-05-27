<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportsAdminController extends Controller
{
    public function topCategories(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $rows = SearchEvent::query()
            ->whereNotNull('category_id')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw('category_id, COUNT(*) as searches')
            ->groupBy('category_id')
            ->orderByDesc('searches')
            ->limit(20)
            ->get();

        $categories = DB::table('categories')
            ->whereIn('id', $rows->pluck('category_id'))
            ->pluck('name', 'id');

        return response()->json([
            'data' => $rows->map(fn ($r) => [
                'category_id' => $r->category_id,
                'category_name' => $categories[$r->category_id] ?? '—',
                'searches' => (int) $r->searches,
            ]),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function topQueries(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $rows = SearchEvent::query()
            ->whereNotNull('query')
            ->where('query', '!=', '')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw('query, COUNT(*) as searches')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(30)
            ->get();

        return response()->json([
            'data' => $rows,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
