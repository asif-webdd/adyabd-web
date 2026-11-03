<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);
        return $this->success($notifications);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return $this->success([], 'Marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return $this->success([], 'All marked as read.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(['count' => $request->user()->unreadNotifications()->count()]);
    }

    /*private function success(mixed $d, string $m = 'Success', int $c = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $m, 'data' => $d], $c);
    }*/
}
