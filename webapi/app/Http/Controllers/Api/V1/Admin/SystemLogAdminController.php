<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class SystemLogAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('system_logs')) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 0],
                'message' => 'La tabla system_logs no existe. Ejecuta migraciones.',
            ]);
        }

        $level = (string) $request->query('level', 'all');
        $channel = (string) $request->query('channel', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = SystemLog::query()
            ->with('user:id,full_name,email,role')
            ->orderByDesc('id');

        if ($level !== 'all' && in_array($level, ['error', 'warning', 'critical', 'alert', 'info'], true)) {
            $query->where('level', $level);
        }
        if ($channel !== 'all' && $channel !== '') {
            $query->where('channel', $channel);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('message', 'like', "%{$q}%")
                    ->orWhere('exception_class', 'like', "%{$q}%")
                    ->orWhere('request_path', 'like', "%{$q}%")
                    ->orWhere('file', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate(min(100, max(10, (int) $request->query('per_page', 25))));

        $data = collect($paginator->items())->map(fn (SystemLog $log) => [
            'id' => $log->id,
            'level' => $log->level,
            'channel' => $log->channel,
            'message' => $log->message,
            'exception_class' => $log->exception_class,
            'file' => $log->file,
            'line' => $log->line,
            'trace' => $log->trace,
            'context' => $log->context,
            'http_status' => $log->http_status,
            'request_method' => $log->request_method,
            'request_path' => $log->request_path,
            'user_id' => $log->user_id,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'full_name' => $log->user->full_name,
                'email' => $log->user->email,
                'role' => $log->user->role,
            ] : null,
            'ip' => $log->ip,
            'created_at' => $log->created_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(int $log): JsonResponse
    {
        $row = SystemLog::query()
            ->with('user:id,full_name,email,role')
            ->findOrFail($log);

        return response()->json([
            'data' => [
                'id' => $row->id,
                'level' => $row->level,
                'channel' => $row->channel,
                'message' => $row->message,
                'exception_class' => $row->exception_class,
                'file' => $row->file,
                'line' => $row->line,
                'trace' => $row->trace,
                'context' => $row->context,
                'http_status' => $row->http_status,
                'request_method' => $row->request_method,
                'request_path' => $row->request_path,
                'user_id' => $row->user_id,
                'user' => $row->user,
                'ip' => $row->ip,
                'created_at' => $row->created_at,
            ],
        ]);
    }
}
