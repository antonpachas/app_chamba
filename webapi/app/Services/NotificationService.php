<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;

final class NotificationService
{
    public function __construct(
        private readonly NotificationEmailService $email,
    ) {}

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
            $notifTitle = 'Nuevo mensaje del cliente';
            $notifBody = "{$client->full_name}: «{$preview}»";
            $this->create(
                $providerUser,
                'service_request.message',
                $notifTitle,
                $notifBody,
                [
                    'service_request_id' => (int) $request->id,
                    'message_id' => (int) $message->id,
                ],
            );
            $this->email->trySend($providerUser, 'service_request.message', $notifTitle, $notifBody, [
                'service_request_id' => (int) $request->id,
            ]);

            return;
        }

        if ($providerUser !== null && (int) $sender->id === (int) $providerUser->id && $client !== null) {
            $from = $request->providerService?->providerProfile?->business_name
                ?: $providerUser->full_name
                ?: 'El negocio';

            $notifTitle = 'Respuesta del negocio';
            $notifBody = "{$from} sobre «{$title}»: «{$preview}»";
            $this->create(
                $client,
                'service_request.message',
                $notifTitle,
                $notifBody,
                [
                    'service_request_id' => (int) $request->id,
                    'message_id' => (int) $message->id,
                ],
            );
            $this->email->trySend($client, 'service_request.message', $notifTitle, $notifBody, [
                'service_request_id' => (int) $request->id,
            ]);
        }
    }

    public function notifyClientRequestStatusUpdated(ServiceRequest $request, string $newStatus): void
    {
        $request->loadMissing([
            'providerService.providerProfile.user',
            'client:id,full_name',
        ]);

        $client = $request->client;
        if ($client === null) {
            return;
        }

        $from = $request->providerService?->providerProfile?->business_name
            ?: $request->providerService?->providerProfile?->user?->full_name
            ?: 'El negocio';
        $title = $request->providerService?->title ?? 'tu solicitud';

        if ($newStatus === 'visto') {
            $notifTitle = 'El negocio vio tu solicitud';
            $notifBody = "{$from} revisó tu mensaje sobre «{$title}».";
            $this->create(
                $client,
                'service_request.seen',
                $notifTitle,
                $notifBody,
                ['service_request_id' => (int) $request->id],
            );
            $this->email->trySend($client, 'service_request.seen', $notifTitle, $notifBody, [
                'service_request_id' => (int) $request->id,
            ]);

            return;
        }

        if ($newStatus === 'cerrado') {
            $notifTitle = 'Solicitud cerrada';
            $notifBody = "{$from} cerró la conversación sobre «{$title}».";
            $this->create(
                $client,
                'service_request.closed',
                $notifTitle,
                $notifBody,
                ['service_request_id' => (int) $request->id],
            );
            $this->email->trySend($client, 'service_request.closed', $notifTitle, $notifBody, [
                'service_request_id' => (int) $request->id,
            ]);
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

        $notifTitle = 'Nuevo contacto';
        $notifBody = "{$clientName} solicitó información sobre «{$title}».";
        $this->create(
            $providerUser,
            'service_request.new',
            $notifTitle,
            $notifBody,
            [
                'service_request_id' => (int) $request->id,
                'provider_service_id' => (int) $request->provider_service_id,
                'client_user_id' => (int) $request->client_user_id,
            ],
        );
        $this->email->trySend($providerUser, 'service_request.new', $notifTitle, $notifBody, [
            'service_request_id' => (int) $request->id,
        ]);
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
