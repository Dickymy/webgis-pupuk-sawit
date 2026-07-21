<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Ambil notifikasi terbaru untuk dropdown bell.
     */
    public function recent()
    {
        $admin = Auth::guard('admin')->user();

        $notifications = $admin->notifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipe' => $n->data['tipe'] ?? 'info',
                'judul' => $n->data['judul'] ?? '',
                'pesan' => $n->data['pesan'] ?? '',
                'url' => $n->data['url'] ?? null,
                'dibaca' => $n->read_at !== null,
                'waktu' => $n->created_at->diffForHumans(),
            ]);

        $unreadCount = $admin->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(string $id)
    {
        $admin = Auth::guard('admin')->user();
        $notification = $admin->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function markAllAsRead()
    {
        $admin = Auth::guard('admin')->user();
        $admin->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
