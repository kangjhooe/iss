<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications for the current user.
     * Query: filter=all|unread|read (default unread), per_page, page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min((int) $request->get('per_page', 20), 50);
        $filter = $request->get('filter', 'unread');

        $query = $user->notifications()->orderBy('created_at', 'desc');
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate($perPage);
        $items = $notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? null,
                'action' => $n->data['action'] ?? null,
                'message' => $n->data['message'] ?? null,
                'data' => $n->data,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ];
        });
        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->unreadNotifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 404);
        }
        $notification->markAsRead();
        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    /**
     * Unread count (for badge).
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = $user->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }
}
