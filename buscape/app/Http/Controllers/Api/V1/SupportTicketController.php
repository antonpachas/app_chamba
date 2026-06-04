<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSupportMessageRequest;
use App\Http\Requests\Api\V1\StoreSupportTicketRequest;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $support,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->support->assertEndUser($user);

        $status = (string) $request->query('status', 'all');

        $query = SupportTicket::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($status !== 'all' && in_array($status, SupportTicket::STATUSES, true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(min(50, max(10, (int) $request->query('per_page', 20))));

        $data = collect($paginator->items())->map(
            fn (SupportTicket $t) => $this->support->formatTicketSummary($t, $user),
        );

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

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->support->assertEndUser($user);
        $data = $request->validated();

        try {
            $ticket = DB::transaction(function () use ($user, $data, $request) {
                $now = Carbon::now();
                $ticket = SupportTicket::query()->create([
                    'user_id' => (int) $user->id,
                    'subject' => $data['subject'],
                    'category' => $data['category'],
                    'status' => 'abierto',
                    'last_message_at' => $now,
                    'user_last_read_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $msg = SupportMessage::query()->create([
                    'support_ticket_id' => (int) $ticket->id,
                    'user_id' => (int) $user->id,
                    'is_staff' => false,
                    'body' => trim($data['body']),
                    'created_at' => $now,
                ]);

                $this->support->storeMessageAttachments($msg, $request);

                return $ticket;
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Caso de soporte creado.',
            'data' => $this->ticketDetail($request, (int) $ticket->id),
        ], 201);
    }

    public function show(Request $request, int $ticket): JsonResponse
    {
        $this->support->assertEndUser($request->user());

        return response()->json([
            'data' => $this->ticketDetail($request, $ticket),
        ]);
    }

    public function storeMessage(StoreSupportMessageRequest $request, int $ticket): JsonResponse
    {
        $user = $request->user();
        $this->support->assertEndUser($user);

        $row = SupportTicket::query()->findOrFail($ticket);
        $this->support->assertUserCanAccess($user, $row);

        if (! $row->userCanReply()) {
            return response()->json([
                'message' => 'Este caso está cerrado. Abre uno nuevo si necesitas ayuda.',
            ], 422);
        }

        try {
            $msg = DB::transaction(function () use ($user, $request, $row) {
                $msg = SupportMessage::query()->create([
                    'support_ticket_id' => (int) $row->id,
                    'user_id' => (int) $user->id,
                    'is_staff' => false,
                    'body' => trim($request->validated('body')),
                    'created_at' => Carbon::now(),
                ]);

                $this->support->storeMessageAttachments($msg, $request);

                return $msg;
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->support->afterUserMessage($row->fresh());
        $row->refresh();
        $this->support->markReadByUser($row);

        $msg->load('author:id,full_name,role');

        return response()->json([
            'message' => 'Mensaje enviado.',
            'data' => $this->support->formatMessage($msg),
            'ticket' => $this->ticketDetail($request, (int) $row->id),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketDetail(Request $request, int $ticketId): array
    {
        $user = $request->user();
        $row = SupportTicket::query()
            ->with(['messages.author:id,full_name,role', 'messages.attachments', 'user:id,full_name,email,role,phone'])
            ->findOrFail($ticketId);

        $this->support->assertUserCanAccess($user, $row);
        $this->support->markReadByUser($row);
        $row->refresh();

        $summary = $this->support->formatTicketSummary($row, $user);
        $summary['messages'] = $row->messages->map(fn (SupportMessage $m) => $this->support->formatMessage($m))->values()->all();

        return $summary;
    }
}
