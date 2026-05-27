<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LedgerAdminController extends Controller
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function index(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $type = $request->query('type');

        $q = LedgerEntry::query()->orderByDesc('occurred_at')->orderByDesc('id');
        if ($from) {
            $q->whereDate('occurred_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('occurred_at', '<=', $to);
        }
        if ($type && in_array($type, ['ingreso', 'egreso'], true)) {
            $q->where('type', $type);
        }

        $rows = $q->limit(500)->get();

        $ingresos = (float) $rows->where('type', 'ingreso')->sum('amount');
        $egresos = (float) $rows->where('type', 'egreso')->sum('amount');

        return response()->json([
            'data' => $rows,
            'summary' => [
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'balance' => $ingresos - $egresos,
            ],
        ]);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:500',
            'occurred_at' => 'required|date',
        ]);

        $entry = $this->ledger->recordManualExpense($data, $request->user());

        return response()->json(['data' => $entry], 201);
    }
}
