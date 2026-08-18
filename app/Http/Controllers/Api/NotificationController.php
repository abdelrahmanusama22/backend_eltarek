<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $user->userNotifications()->paginate(20);

        return $this->ok([
            'unread_count' => $user->userNotifications()->where('is_read', false)->count(),
            'notifications' => collect($page->items())->map->toApi(),
        ], meta: [
            'current_page' => $page->currentPage(),
            'total' => $page->total(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->userNotifications()->update(['is_read' => true]);

        return $this->ok(['unread_count' => 0]);
    }
}
