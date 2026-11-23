<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $notifications = $user->notifications()
            ->with('concern')
            ->latest()
            ->take(20)
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    public function read(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403, __('messages.forbidden'));

        $notification->markAsRead();

        if ($notification->concern !== null) {
            return redirect()->route('concerns.show', $notification->concern);
        }

        return back();
    }

    public function readAll(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', __('messages.notifications_marked_read'));
    }
}
