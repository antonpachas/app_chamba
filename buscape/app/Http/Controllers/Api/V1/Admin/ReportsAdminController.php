<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function users(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $totals = DB::table('users')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN role = 'cliente' THEN 1 ELSE 0 END) as clientes,
                SUM(CASE WHEN role = 'proveedor' THEN 1 ELSE 0 END) as proveedores,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspendidos
            ")
            ->first();

        $byDay = DB::table('users')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as total, role")
            ->groupBy('day', 'role')
            ->orderBy('day')
            ->get()
            ->groupBy('day')
            ->map(fn ($rows) => [
                'day' => $rows->first()->day,
                'clientes' => $rows->where('role', 'cliente')->sum('total'),
                'proveedores' => $rows->where('role', 'proveedor')->sum('total'),
                'total' => $rows->sum('total'),
            ])
            ->values();

        $proCount = DB::table('user_subscriptions as us')
            ->join('subscription_plans as sp', 'us.plan_id', '=', 'sp.id')
            ->where('us.status', 'active')
            ->where('sp.is_pro', 1)
            ->count();

        return response()->json([
            'data' => [
                'totals' => $totals,
                'by_day' => $byDay,
                'pro_active' => $proCount,
            ],
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function listings(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $totals = DB::table('provider_services')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as ocultos,
                SUM(CASE WHEN home_featured = 1 THEN 1 ELSE 0 END) as destacados
            ")
            ->first();

        $byDay = DB::table('provider_services')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topCategories = DB::table('provider_services as ps')
            ->join('categories as c', 'ps.category_id', '=', 'c.id')
            ->where('ps.status', 'active')
            ->selectRaw('c.name as category_name, COUNT(*) as total')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $contactsThisMonth = 0;
        if (Schema::hasTable('service_requests')) {
            $contactsThisMonth = DB::table('service_requests')
                ->whereDate('created_at', '>=', now()->startOfMonth())
                ->count();
        }

        return response()->json([
            'data' => [
                'totals' => $totals,
                'by_day' => $byDay,
                'top_categories' => $topCategories,
                'contacts_this_month' => $contactsThisMonth,
            ],
            'from' => $from,
            'to' => $to,
        ]);
    }
}
