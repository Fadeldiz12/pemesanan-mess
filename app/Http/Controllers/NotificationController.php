<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * GET (bukan POST) supaya bisa langsung dipakai sebagai href link biasa
     * di dropdown notifikasi - tandai dibaca lalu redirect ke halaman
     * terkait, satu klik. Scoped lewat relasi notifications() milik user
     * yang login, jadi notifikasi milik orang lain otomatis 404.
     */
    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return redirect($notification->data['url'] ?? route('notifications.index'));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
