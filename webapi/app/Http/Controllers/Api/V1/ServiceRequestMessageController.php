<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServiceRequestMessageRequest;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class ServiceRequestMessageController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request, int $serviceRequest): JsonResponse
    {
        $sr = $this->findAuthorized($request, $serviceRequest);
        $sr->loadMissing(['client:id,full_name', 'messages.author:id,full_name,role']);

        return response()->json([
            'data' => $this->buildThread($sr),
            'can_post' => $this->canPost($sr),
        ]);
    }

    public function store(StoreServiceRequestMessageRequest $request, int $serviceRequest): JsonResponse
    {
        $user = $request->user();
        $sr = $this->findAuthorized($request, $serviceRequest);

        if (! $this->canPost($sr)) {
            return response()->json([
                'message' => 'Esta solicitud está cerrada. Ya no puedes enviar mensajes.',
            ], 422);
        }

        $role = $this->participantRole($user, $sr);
        if ($role === null) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $msg = ServiceRequestMessage::query()->create([
            'service_request_id' => (int) $sr->id,
            'user_id' => (int) $user->id,
            'author_role' => $role,
            'body' => trim($request->validated('body')),
            'created_at' => Carbon::now(),
        ]);

        if ($role === 'proveedor' && $sr->status === 'nuevo') {
            $sr->update(['status' => 'visto']);
        }

        $msg->load('author:id,full_name,role');
        $sr->loadMissing(['client:id,full_name', 'providerService.providerProfile.user:id,full_name']);

        $this->notifications->notifyServiceRequestMessage($sr, $msg, $user);

        return response()->json([
            'message' => 'Mensaje enviado.',
            'data' => $this->formatMessage($msg),
            'thread' => $this->buildThread($sr->fresh(['client', 'messages.author'])),
            'can_post' => $this->canPost($sr->fresh()),
        ], 201);
    }

    private function findAuthorized(Request $request, int $serviceRequestId): ServiceRequest
    {
        $sr = ServiceRequest::query()
            ->with([
                'client:id,full_name',
                'providerService:id,provider_profile_id,title',
                'providerService.providerProfile:id,user_id,business_name',
                'providerService.providerProfile.user:id,full_name',
            ])
            ->findOrFail($serviceRequestId);

        if ($this->participantRole($request->user(), $sr) === null) {
            abort(403, 'No autorizado.');
        }

        return $sr;
    }

    private function participantRole(User $user, ServiceRequest $sr): ?string
    {
        if ((int) $sr->client_user_id === (int) $user->id) {
            return 'cliente';
        }

        $providerUserId = $sr->providerService?->providerProfile?->user_id;
        if ($providerUserId !== null && (int) $providerUserId === (int) $user->id) {
            return 'proveedor';
        }

        return null;
    }

    private function canPost(ServiceRequest $sr): bool
    {
        return ! in_array((string) $sr->status, ['cerrado', 'cancelado'], true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildThread(ServiceRequest $sr): array
    {
        $thread = [];

        if ($sr->message) {
            $thread[] = [
                'id' => null,
                'body' => $sr->message,
                'author_role' => 'cliente',
                'author_name' => $sr->client?->full_name ?? 'Cliente',
                'created_at' => $sr->created_at?->toIso8601String(),
                'is_initial' => true,
            ];
        }

        foreach ($sr->messages ?? [] as $msg) {
            $thread[] = $this->formatMessage($msg);
        }

        return $thread;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(ServiceRequestMessage $msg): array
    {
        return [
            'id' => $msg->id,
            'body' => $msg->body,
            'author_role' => $msg->author_role,
            'author_name' => $msg->author?->full_name ?? ($msg->author_role === 'proveedor' ? 'Proveedor' : 'Cliente'),
            'created_at' => $msg->created_at?->toIso8601String(),
            'is_initial' => false,
        ];
    }
}
