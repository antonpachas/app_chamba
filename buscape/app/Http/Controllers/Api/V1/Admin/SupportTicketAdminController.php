<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateSupportTicketStatusRequest;
use App\Http\Requests\Api\V1\StoreSupportMessageRequest;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SupportTicketAdminController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $support,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');
        $onlyUnread = $request->boolean('only_unread');

        $query = SupportTicket::query()
            ->with('user:id,full_name,email,role,phone')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($status !== 'all' && in_array($status, SupportTicket::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($role !== 'all' && in_array($role, ['cliente', 'proveedor'], true)) {
            $query->whereHas('user', fn ($sub) => $sub->where('role', $role));
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('subject', 'like', "%{$q}%")
                    ->orWhere('id', $q)
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('full_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $paginator = $query->paginate(min(100, max(10, (int) $request->query('per_page', 25))));

        $admin = $request->user();
        $items = collect($paginator->items());
        $data = $items
            ->map(fn (SupportTicket $t) => $this->support->formatTicketSummary($t, $admin))
            ->when($onlyUnread, fn ($c) => $c->filter(fn (array $r) => ! empty($r['unread_for_admin'])))
            ->values();

        $openCount = SupportTicket::query()
            ->whereNotIn('status', ['cerrado', 'resuelto'])
            ->count();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'open_count' => $openCount,
            ],
        ]);
    }

    public function show(Request $request, int $ticket): JsonResponse
    {
        return response()->json([
            'data' => $this->ticketDetail($request, $ticket),
        ]);
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, int $ticket): JsonResponse
    {
        $row = SupportTicket::query()->findOrFail($ticket);
        $this->support->applyStatus($row, $request->validated('status'));
        $row->refresh();

        return response()->json([
            'message' => 'Estado actualizado.',
            'data' => $this->ticketDetail($request, (int) $row->id),
        ]);
    }

    public function storeMessage(StoreSupportMessageRequest $request, int $ticket): JsonResponse
    {
        $admin = $request->user();
        $row = SupportTicket::query()->findOrFail($ticket);

        if ($row->isClosed()) {
            return response()->json([
                'message' => 'El caso está cerrado. Cambia el estado antes de responder.',
            ], 422);
        }

        $msg = DB::transaction(function () use ($admin, $request, $row) {
            $msg = SupportMessage::query()->create([
                'support_ticket_id' => (int) $row->id,
                'user_id' => (int) $admin->id,
                'is_staff' => true,
                'body' => trim($request->validated('body')),
                'created_at' => Carbon::now(),
            ]);

            $this->support->afterStaffMessage($row->fresh());

            return $msg;
        });

        $row->refresh();
        $this->support->markReadByAdmin($row);
        $msg->load('author:id,full_name,role');

        return response()->json([
            'message' => 'Respuesta enviada.',
            'data' => $this->support->formatMessage($msg),
            'ticket' => $this->ticketDetail($request, (int) $row->id),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketDetail(Request $request, int $ticketId): array
    {
        $admin = $request->user();
        $row = SupportTicket::query()
            ->with(['messages.author:id,full_name,role', 'messages.attachments', 'user:id,full_name,email,role,phone'])
            ->findOrFail($ticketId);

        $this->support->markReadByAdmin($row);
        $row->refresh();

        $summary = $this->support->formatTicketSummary($row, $admin);
        $summary['messages'] = $row->messages->map(fn (SupportMessage $m) => $this->support->formatMessage($m))->values()->all();

        return $summary;
    }
}
