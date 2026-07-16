<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationIndexRequest;
use App\Services\NotifikasiService;
use App\Models\Notifikasi;
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
        $filters = $request->only(['periode', 'jenis_notifikasi', 'status', 'sudah_dibaca', 'search']);

        $notifikasi = $this->notificationService->getAdminNotifications($filters, $perPage)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'member_name' => $notification->anggota?->user?->nama,
                'title' => $notification->judul,
                'message' => $notification->pesan,
                'no_telp' => $notification->anggota?->user?->no_telp,
                'notification_type' => $notification->jenis_notifikasi,
                'reminder_type' => $notification->jenis_pengingat,
                'status' => $notification->status,
                'is_read' => $notification->sudah_dibaca,
                'scheduled_at' => optional($notification->dijadwalkan_pada)?->format('d/m/Y H:i'),
                'sent_at' => optional($notification->dikirim_pada)?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Notifications/Index', [
            'notifikasi' => $notifikasi,
            'filters' => $filters,
        ]);
    }

    public function show(Notifikasi $notification)
    {
        $notification->load('anggota.user');

        return Inertia::render('Admin/Notifications/Show', [
            'notification' => [
                'id' => $notification->id,
                'member_name' => $notification->anggota?->user?->nama,
                'title' => $notification->judul,
                'message' => $notification->pesan,
                'notification_type' => $notification->jenis_notifikasi,
                'jenis_referensi' => $notification->jenis_referensi,
                'referensi_id' => $notification->referensi_id,
                'periode_notifikasi' => $notification->periode_notifikasi,
                'reminder_type' => $notification->jenis_pengingat,
                'status' => $notification->status,
                'is_read' => $notification->sudah_dibaca,
                'scheduled_at' => optional($notification->dijadwalkan_pada)?->format('d/m/Y H:i'),
                'sent_at' => optional($notification->dikirim_pada)?->format('d/m/Y H:i'),
                'read_at' => optional($notification->dibaca_pada)?->format('d/m/Y H:i'),
                'created_at' => optional($notification->created_at)?->format('d/m/Y H:i'),
                'updated_at' => optional($notification->updated_at)?->format('d/m/Y H:i'),
            ],
        ]);
    }
}
