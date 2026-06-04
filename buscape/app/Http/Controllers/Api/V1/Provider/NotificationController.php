<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $onlyUnread = $request->boolean('unread');

        $query = UserNotification::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('created_at')
            ->limit(30);

        if ($onlyUnread) {
            $query->whereNull('read_at');
        }

        $rows = $query->get();

        return response()->json([
            'data' => $rows->map(static fn (UserNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]),
            'unread_count' => $this->notifications->unreadCount($user),
        ]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $this->notifications->markRead($request->user(), $notification);

        return response()->json([
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllRead($request->user());

        return response()->json(['unread_count' => 0]);
    }
}
