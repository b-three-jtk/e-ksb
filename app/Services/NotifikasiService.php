<?php

namespace App\Services;

use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Angsuran;
use App\Models\Anggota;
use App\Models\Notifikasi;
use Illuminate\Support\Carbon;

class NotifikasiService
{
    public function findByDuplicateCriteria(
        string $anggotaId,
        string $type,
        string $period,
        string $reminderType,
        ?string $referenceId = null
    ): ?Notifikasi {
        $query = Notifikasi::where('anggota_id', $anggotaId)
            ->where('jenis_notifikasi', $type)
            ->where('periode_notifikasi', $period)
            ->where('jenis_pengingat', $reminderType);

        if ($referenceId) {
            $query->where('referensi_id', $referenceId);
        }

        return $query->first();
    }

    public function getAdminList(array $filters, int $perPage = 10, ?string $pjUserId = null)
    {
        $query = Notifikasi::with(['anggota.user'])
            ->when($pjUserId, function ($query, $pjUserId) {
                $query->whereHas('anggota', function ($memberQuery) use ($pjUserId) {
                    $memberQuery->where('pj_anggota_id', $pjUserId);
                });
            })
            ->orderBy('dijadwalkan_pada', 'desc');

        if (!empty($filters['periode'])) {
            $query->where('periode_notifikasi', $filters['periode']);
        }

        if (!empty($filters['jenis_notifikasi'])) {
            $query->where('jenis_notifikasi', $filters['jenis_notifikasi']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['sudah_dibaca']) && $filters['sudah_dibaca'] !== '') {
            $query->where('sudah_dibaca', filter_var($filters['sudah_dibaca'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $query->whereHas('anggota.user', function ($q) use ($filters) {
                $q->where('nama', 'ILIKE', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function sendReminderNotifications(): void
    {
        $this->processMandatorySavingReminders();
        $this->processInstallmentReminders();
    }

    public function processMandatorySavingReminders(): void
    {
        $today = now()->startOfDay();
        $dueDate = now()->endOfMonth();
        $daysLeft = $today->diffInDays($dueDate, false);

        $reminderType = $this->matchReminderType($daysLeft);
        if (!$reminderType) {
            return;
        }

        $currentPeriod = now()->format('Y-m');
        $anggota = Anggota::whereHas('akunSimpanan')
            ->whereDoesntHave('akunSimpanan.transactions', function ($query) {
                $query->where('kode_transaksi_simpanan', 'ILIKE', 'SW%')
                    ->where('tipe_transaksi', 'Penyetoran')
                    ->whereMonth('tgl_transaksi', now()->month)
                    ->whereYear('tgl_transaksi', now()->year);
            })
            ->get();

        foreach ($anggota as $a) {
            if ($this->findByDuplicateCriteria(
                $a->id,
                NotificationTypeEnum::MANDATORY_SAVING->value,
                $currentPeriod,
                $reminderType->value
            )) {
                continue;
            }

            $notification = Notifikasi::create([
                'anggota_id' => $a->id,
                'judul' => 'Pengingat Simpanan Wajib Bulan ' . now()->locale('id')->isoFormat('MMMM YYYY'),
                'pesan' => 'Simpanan wajib untuk periode ' . now()->locale('id')->isoFormat('MMMM YYYY') . ' jatuh tempo pada ' . $dueDate->locale('id')->translatedFormat('d F Y') . '. Pastikan Anda melakukan setoran sebelum jatuh tempo.',
                'jenis_notifikasi' => NotificationTypeEnum::MANDATORY_SAVING->value,
                'jenis_referensi' => null,
                'referensi_id' => null,
                'periode_notifikasi' => $currentPeriod,
                'jenis_pengingat' => $reminderType->value,
                'status' => NotificationStatusEnum::DRAFT->value,
                'sudah_dibaca' => false,
                'dijadwalkan_pada' => now(),
            ]);

            $this->deliverNotification($notification);
        }
    }

    public function processInstallmentReminders(): void
    {
        $today = now()->startOfDay();
        $angsuran = Angsuran::with('pembiayaan.anggota')
            ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
            ->whereBetween('tgl_jatuh_tempo', [$today, $today->copy()->addDays(7)])
            ->get();

        foreach ($angsuran as $a) {
            $dueDate = Carbon::parse($a->tgl_jatuh_tempo)->startOfDay();
            $daysLeft = $today->diffInDays($dueDate, false);
            $reminderType = $this->matchReminderType($daysLeft);
            if (!$reminderType || !$a->pembiayaan?->anggota) {
                continue;
            }

            $period = $dueDate->format('Y-m');
            $anggotaId = $a->pembiayaan->anggota->id;

            if ($this->findByDuplicateCriteria(
                $anggotaId,
                NotificationTypeEnum::INSTALLMENT->value,
                $period,
                $reminderType->value,
                $a->id
            )) {
                continue;
            }

            $notification = Notifikasi::create([
                'anggota_id' => $anggotaId,
                'judul' => 'Pengingat Angsuran Pembiayaan #' . $a->angsuran_ke,
                'pesan' => 'Angsuran ke-' . $a->angsuran_ke . ' sebesar Rp ' . number_format($a->nominal_angsuran, 0, ',', '.') . ' jatuh tempo pada ' . $dueDate->locale('id')->translatedFormat('d F Y') . '.',
                'jenis_notifikasi' => NotificationTypeEnum::INSTALLMENT->value,
                'jenis_referensi' => Angsuran::class,
                'referensi_id' => $a->id,
                'periode_notifikasi' => $period,
                'jenis_pengingat' => $reminderType->value,
                'status' => NotificationStatusEnum::DRAFT->value,
                'sudah_dibaca' => false,
                'dijadwalkan_pada' => now(),
            ]);

            $this->deliverNotification($notification);
        }
    }

    public function matchReminderType(int $daysLeft): ?NotificationReminderTypeEnum
    {
        return match ($daysLeft) {
            7 => NotificationReminderTypeEnum::H_7,
            3 => NotificationReminderTypeEnum::H_3,
            0 => NotificationReminderTypeEnum::H_0,
            default => null,
        };
    }

    public function deliverNotification(Notifikasi $notification): void
    {
        try {
            $notification->status = NotificationStatusEnum::SENT->value;
            $notification->dikirim_pada = now();
            $notification->save();
        } catch (\Throwable $exception) {
            report($exception);
            $notification->status = NotificationStatusEnum::FAILED->value;
            $notification->save();
        }
    }

    public function getAdminNotifications(array $filters, int $perPage = 10)
    {
        $pjUserId = auth()->id();
        $isPj = auth()->user()?->hasRole(UserRoleEnum::PJANGGOTA->value) ?? false;

        return $this->getAdminList($filters, $perPage, $isPj ? $pjUserId : null);
    }

    public function getMemberNotifications(string $anggotaId, bool $unreadOnly = false, int $perPage = 10)
    {
        $query = Notifikasi::with('angsuran')
            ->where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->orderBy('dijadwalkan_pada', 'desc');

        if ($unreadOnly) {
            $query->where('sudah_dibaca', false);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function markAsRead(Notifikasi $notification): void
    {
        if (!$notification->sudah_dibaca) {
            $notification->update([
                'sudah_dibaca' => true,
                'dibaca_pada' => now(),
            ]);
        }
    }

    public function markAllAsRead(string $anggotaId): void
    {
        Notifikasi::where('anggota_id', $anggotaId)
            ->where('sudah_dibaca', false)
            ->update([
                'sudah_dibaca' => true,
                'dibaca_pada' => now(),
            ]);
    }

    public function markPopupDisplayed(array $ids, string $anggotaId): void
    {
        Notifikasi::where('anggota_id', $anggotaId)
            ->whereIn('id', $ids)
            ->whereNull('peringatan_ditampilkan_pada')
            ->update(['peringatan_ditampilkan_pada' => now()]);
    }

    public function getNotificationDropdown(string $anggotaId): array
    {
        return Notifikasi::where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->orderBy('dijadwalkan_pada', 'desc')
            ->limit(5)
            ->get()
            ->map(fn(Notifikasi $notification) => [
                'id' => $notification->id,
                'title' => $notification->judul,
                'message' => $notification->pesan,
                'is_read' => $notification->sudah_dibaca,
                'scheduled_at' => $notification->dijadwalkan_pada?->format('Y-m-d H:i:s'),
                'href' => route('user.notifikasi.show', ['notification' => $notification->id]),
            ])
            ->toArray();
    }

    public function getUnreadCount(string $anggotaId): int
    {
        return Notifikasi::where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->where('sudah_dibaca', false)
            ->count();
    }

    public function getPendingPopupNotifications(string $anggotaId): array
    {
        return Notifikasi::where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->where('sudah_dibaca', false)
            ->whereNull('peringatan_ditampilkan_pada')
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(Notifikasi $notification) => [
                'id' => $notification->id,
                'title' => $notification->judul,
                'message' => $notification->pesan,
            ])
            ->toArray();
    }
}
