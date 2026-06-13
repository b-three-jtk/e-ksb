<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\MarkAllNotificationsReadRequest;
use App\Http\Requests\User\MarkNotificationPopupDisplayedRequest;
use App\Models\Notification;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotifikasiController extends Controller
{
    public function __construct(private NotifikasiService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $unreadOnly = $request->boolean('unread', false);
        $memberId = auth()->user()->member->id;

        $notifications = $this->notificationService->getMemberNotifications($memberId, $unreadOnly, $perPage)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'notification_type' => $notification->notification_type,
                'reminder_type' => $notification->reminder_type,
                'status' => $notification->status,
                'is_read' => $notification->is_read,
                'scheduled_at' => optional($notification->scheduled_at)?->format('d/m/Y H:i'),
                'sent_at' => optional($notification->sent_at)?->format('d/m/Y H:i'),
                'read_at' => optional($notification->read_at)?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('User/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'unread' => $unreadOnly,
            ],
        ]);
    }

    public function show(Notification $notification)
    {
        if ($notification->member_id !== auth()->user()->member->id) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        return Inertia::render('User/Notifications/Show', [
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'notification_type' => $notification->notification_type,
                'reminder_type' => $notification->reminder_type,
                'status' => $notification->status,
                'is_read' => $notification->is_read,
                'scheduled_at' => optional($notification->scheduled_at)?->format('d/m/Y H:i'),
                'sent_at' => optional($notification->sent_at)?->format('d/m/Y H:i'),
                'read_at' => optional($notification->read_at)?->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function markAllAsRead(MarkAllNotificationsReadRequest $request)
    {
        $this->notificationService->markAllAsRead(auth()->user()->member->id);

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }

    public function markPopupDisplayed(MarkNotificationPopupDisplayedRequest $request)
    {
        $this->notificationService->markPopupDisplayed($request->input('notification_ids'), auth()->user()->member->id);

        return response()->json(['message' => 'Popup notification status updated']);
    }
}
