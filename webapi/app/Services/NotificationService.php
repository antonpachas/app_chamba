<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;

final class NotificationService
{
    public function notifyServiceRequestMessage(ServiceRequest $request, ServiceRequestMessage $message, User $sender): void
    {
        $request->loadMissing([
            'providerService.providerProfile.user',
            'client:id,full_name',
        ]);

        $providerUser = $request->providerService?->providerProfile?->user;
        $client = $request->client;
        $title = $request->providerService?->title ?? 'tu solicitud';
        $preview = mb_strlen($message->body) > 80
            ? mb_substr($message->body, 0, 77).'…'
            : $message->body;

        if ((int) $sender->id === (int) $client?->id && $providerUser !== null) {
            $this->create(
                $providerUser,
                'service_request.message',
                'Nuevo mensaje del cliente',
                "{$client->full_name}: «{$preview}»",
                [
                    'service_request_id' => (int) $request->id,
                    'message_id' => (int) $message->id,
                ],
            );

            return;
        }

        if ($providerUser !== null && (int) $sender->id === (int) $providerUser->id && $client !== null) {
            $from = $request->providerService?->providerProfile?->business_name
                ?: $providerUser->full_name
                ?: 'El negocio';

            $this->create(
                $client,
                'service_request.message',
                'Respuesta del negocio',
                "{$from} sobre «{$title}»: «{$preview}»",
                [
                    'service_request_id' => (int) $request->id,
                    'message_id' => (int) $message->id,
                ],
            );
        }
    }

    public function notifyProviderNewRequest(ServiceRequest $request): void
    {
        $request->loadMissing([
            'providerService.providerProfile.user',
            'client:id,full_name',
        ]);

        $providerUser = $request->providerService?->providerProfile?->user;
        if ($providerUser === null) {
            return;
        }

        $clientName = $request->client?->full_name ?? 'Un cliente';
        $title = $request->providerService?->title ?? 'tu anuncio';

        $this->create(
            $providerUser,
            'service_request.new',
            'Nuevo contacto',
            "{$clientName} solicitó información sobre «{$title}».",
            [
                'service_request_id' => (int) $request->id,
                'provider_service_id' => (int) $request->provider_service_id,
                'client_user_id' => (int) $request->client_user_id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function create(User $user, string $type, string $title, ?string $body = null, ?array $data = null): UserNotification
    {
        return UserNotification::query()->create([
            'user_id' => (int) $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'created_at' => Carbon::now(),
        ]);
    }

    public function unreadCount(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, int $notificationId): void
    {
        UserNotification::query()
            ->where('user_id', (int) $user->id)
            ->where('id', $notificationId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAllRead(User $user): void
    {
        UserNotification::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
