<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\MarkAllNotificationsReadRequest;
use App\Http\Requests\User\MarkNotificationPopupDisplayedRequest;
use App\Models\Notifikasi;
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
        $anggotaId = auth()->user()->anggota->id;

        $notifikasi = $this->notificationService->getMemberNotifications($anggotaId, $unreadOnly, $perPage)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'judul' => $notification->judul,
                'pesan' => $notification->pesan,
                'jenis_notifikasi' => $notification->jenis_notifikasi,
                'jenis_pengingat' => $notification->jenis_pengingat,
                'status' => $notification->status,
                'sudah_dibaca' => $notification->sudah_dibaca,
                'dijadwalkan_pada' => optional($notification->dijadwalkan_pada)?->format('d/m/Y H:i'),
                'dikirim_pada' => optional($notification->dikirim_pada)?->format('d/m/Y H:i'),
                'dibaca_pada' => optional($notification->dibaca_pada)?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('User/Notifications/Index', [
            'notifikasi' => $notifikasi,
            'filters' => [
                'unread' => $unreadOnly,
            ],
        ]);
    }

    public function show(Notifikasi $notification)
    {
        if ($notification->anggota_id !== auth()->user()->anggota->id) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        return Inertia::render('User/Notifications/Show', [
            'notification' => [
                'id' => $notification->id,
                'judul' => $notification->judul,
                'pesan' => $notification->pesan,
                'jenis_notifikasi' => $notification->jenis_notifikasi,
                'jenis_pengingat' => $notification->jenis_pengingat,
                'status' => $notification->status,
                'sudah_dibaca' => $notification->sudah_dibaca,
                'dijadwalkan_pada' => optional($notification->dijadwalkan_pada)?->format('d/m/Y H:i'),
                'dikirim_pada' => optional($notification->dikirim_pada)?->format('d/m/Y H:i'),
                'dibaca_pada' => optional($notification->dibaca_pada)?->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function markAllAsRead(MarkAllNotificationsReadRequest $request)
    {
        $this->notificationService->markAllAsRead(auth()->user()->anggota->id);

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }

    public function markPopupDisplayed(MarkNotificationPopupDisplayedRequest $request)
    {
        $this->notificationService->markPopupDisplayed($request->input('notification_ids'), auth()->user()->anggota->id);

        return response()->json(['pesan' => 'Popup notification status updated']);
    }
}
