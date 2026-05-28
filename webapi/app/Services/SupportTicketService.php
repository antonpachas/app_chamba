<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Carbon;

final class SupportTicketService
{
    public function assertUserCanAccess(User $user, SupportTicket $ticket): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ((int) $ticket->user_id !== (int) $user->id) {
            abort(403, 'No autorizado.');
        }
    }

    public function assertEndUser(User $user): void
    {
        if (! in_array($user->role, ['cliente', 'proveedor'], true)) {
            abort(403, 'Solo clientes y proveedores pueden abrir casos de soporte.');
        }
    }

    public function markReadByUser(SupportTicket $ticket): void
    {
        $ticket->update(['user_last_read_at' => Carbon::now()]);
    }

    public function markReadByAdmin(SupportTicket $ticket): void
    {
        $ticket->update(['admin_last_read_at' => Carbon::now()]);
    }

    public function afterUserMessage(SupportTicket $ticket): void
    {
        if ($ticket->status === 'cerrado') {
            return;
        }

        $ticket->update([
            'status' => 'pendiente_soporte',
            'last_message_at' => Carbon::now(),
        ]);
    }

    public function afterStaffMessage(SupportTicket $ticket, ?string $status = null): void
    {
        if ($ticket->status === 'cerrado') {
            return;
        }

        $next = $status ?? match ($ticket->status) {
            'abierto' => 'en_progreso',
            'pendiente_soporte' => 'esperando_usuario',
            default => 'esperando_usuario',
        };

        $ticket->update([
            'status' => $next,
            'last_message_at' => Carbon::now(),
        ]);
    }

    public function applyStatus(SupportTicket $ticket, string $status): void
    {
        if (! in_array($status, SupportTicket::STATUSES, true)) {
            throw new \InvalidArgumentException('Estado no válido.');
        }

        $payload = ['status' => $status];
        if (in_array($status, ['cerrado', 'resuelto'], true)) {
            $payload['closed_at'] = Carbon::now();
        } elseif ($ticket->closed_at !== null && ! in_array($status, ['cerrado', 'resuelto'], true)) {
            $payload['closed_at'] = null;
        }

        $ticket->update($payload);
    }

    public function unreadForAdmin(SupportTicket $ticket): bool
    {
        $since = $ticket->admin_last_read_at ?? $ticket->created_at;

        return SupportMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->where('is_staff', false)
            ->when($since, fn ($q) => $q->where('created_at', '>', $since))
            ->exists();
    }

    public function unreadForUser(SupportTicket $ticket): bool
    {
        $since = $ticket->user_last_read_at ?? $ticket->created_at;

        return SupportMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->where('is_staff', true)
            ->when($since, fn ($q) => $q->where('created_at', '>', $since))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatMessage(SupportMessage $msg): array
    {
        $msg->loadMissing('author:id,full_name,role');

        return [
            'id' => $msg->id,
            'body' => $msg->body,
            'is_staff' => (bool) $msg->is_staff,
            'author_name' => $msg->is_staff
                ? 'Soporte Busca PE'
                : ($msg->author?->full_name ?? 'Usuario'),
            'author_role' => $msg->author?->role,
            'created_at' => $msg->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatTicketSummary(SupportTicket $ticket, User $viewer): array
    {
        $ticket->loadMissing('user:id,full_name,email,role,phone');

        $row = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'last_message_at' => $ticket->last_message_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'can_reply' => $viewer->role === 'admin' || $ticket->userCanReply(),
        ];

        if ($viewer->role === 'admin') {
            $row['user'] = [
                'id' => $ticket->user->id,
                'full_name' => $ticket->user->full_name,
                'email' => $ticket->user->email,
                'role' => $ticket->user->role,
                'phone' => $ticket->user->phone,
            ];
            $row['unread_for_admin'] = $this->unreadForAdmin($ticket);
        } else {
            $row['unread_for_user'] = $this->unreadForUser($ticket);
        }

        return $row;
    }
}
