<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends BaseController
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'title' => 'Notifications',
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();
        LeadMaxChatController::forgetUnreadCounts(auth()->user());

        return redirect()->back();
    }

    public function read(string $id): RedirectResponse
    {
        $notification = LeadMaxChatController::visibleNotifications(auth()->user())->findOrFail($id);

        if (! $notification->read_at) {
            $notification->markAsRead();
            LeadMaxChatController::forgetUnreadCounts(auth()->user());
        }

        $link = $notification->data['meta']['link'] ?? null;

        // Opened from CRM-Max, a notification without a link returns to CRM-Max's list, not the classic one.
        $fallback = request('from') === 'leadmax' ? 'admin.leadmax.notifications' : 'admin.notifications.index';

        return $link ? redirect($link) : redirect()->route($fallback);
    }
}
