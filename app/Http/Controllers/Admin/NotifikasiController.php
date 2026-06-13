<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationIndexRequest;
use App\Services\NotifikasiService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotifikasiController extends Controller
{
    public function __construct(private NotifikasiService $notificationService)
    {
    }

    public function index(NotificationIndexRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $filters = $request->only(['periode', 'notification_type', 'status', 'is_read', 'search']);

        $notifications = $this->notificationService->getAdminNotifications($filters, $perPage)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'member_name' => $notification->member?->user?->name,
                'title' => $notification->title,
                'message' => $notification->message,
                'phone_number' => $notification->member?->user?->phone_number,
                'notification_type' => $notification->notification_type,
                'reminder_type' => $notification->reminder_type,
                'status' => $notification->status,
                'is_read' => $notification->is_read,
                'scheduled_at' => optional($notification->scheduled_at)?->format('d/m/Y H:i'),
                'sent_at' => optional($notification->sent_at)?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $filters,
        ]);
    }

    public function show(Notification $notification)
    {
        $notification->load('member.user');

        return Inertia::render('Admin/Notifications/Show', [
            'notification' => [
                'id' => $notification->id,
                'member_name' => $notification->member?->user?->name,
                'title' => $notification->title,
                'message' => $notification->message,
                'notification_type' => $notification->notification_type,
                'reference_type' => $notification->reference_type,
                'reference_id' => $notification->reference_id,
                'notification_period' => $notification->notification_period,
                'reminder_type' => $notification->reminder_type,
                'status' => $notification->status,
                'is_read' => $notification->is_read,
                'scheduled_at' => optional($notification->scheduled_at)?->format('d/m/Y H:i'),
                'sent_at' => optional($notification->sent_at)?->format('d/m/Y H:i'),
                'read_at' => optional($notification->read_at)?->format('d/m/Y H:i'),
                'created_at' => optional($notification->created_at)?->format('d/m/Y H:i'),
                'updated_at' => optional($notification->updated_at)?->format('d/m/Y H:i'),
            ],
        ]);
    }
}
