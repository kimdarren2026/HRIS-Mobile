<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $returnUrl = $this->safeReturnUrl($request->query('return_url'));

        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20)
            ->appends($returnUrl ? ['return_url' => $returnUrl] : []);

        return view('pages.notifications.index', compact('notifications', 'returnUrl'));
    }

    public function show(Request $request, Notification $notification): View
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $returnUrl = $this->safeReturnUrl($request->query('return_url'));

        return view('pages.notifications.show', compact('notification', 'returnUrl'));
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $notification->update(['is_read' => true]);

        $returnUrl = $this->safeReturnUrl($request->input('return_url'));

        return redirect()->route('notifications.index', $returnUrl ? ['return_url' => $returnUrl] : []);
    }

    public function readAll(Request $request): RedirectResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $returnUrl = $this->safeReturnUrl($request->input('return_url'));

        return redirect()->route('notifications.index', $returnUrl ? ['return_url' => $returnUrl] : []);
    }

    private function safeReturnUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        // Reject control characters
        if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return null;
        }
        // Reject absolute and protocol-relative URLs
        if (str_contains($url, '://') || str_starts_with($url, '//')) {
            return null;
        }
        // Must be a root-relative path
        if (! str_starts_with($url, '/')) {
            return null;
        }
        // Reject private file-serving paths
        foreach (['/attendance/photo', '/leave/attachment', '/storage/'] as $blocked) {
            if (str_starts_with($url, $blocked)) {
                return null;
            }
        }
        return $url;
    }
}
