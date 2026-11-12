<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Get recent notifications as JSON or page.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $notifications = $user->notifications()->take(20)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Pemberitahuan Sistem',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'category' => $n->data['category'] ?? 'info',
                'data' => $n->data,
                'read_at' => $n->read_at,
                'time_ago' => $n->created_at?->diffForHumans() ?? 'Baru saja',
            ];
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(string $id, Request $request): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    /**
     * Remove a notification.
     */
    public function destroy(string $id, Request $request): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
