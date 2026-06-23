<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('pages.notifications.index', compact('notifications'));
    }

    public function show(Notification $notification): View
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        return view('pages.notifications.show', compact('notification'));
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $notification->update(['is_read' => true]);

        return redirect()->route('notifications.index');
    }

    public function readAll(): RedirectResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return redirect()->route('notifications.index');
    }
}
