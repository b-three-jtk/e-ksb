<?php

namespace App\Services;

use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Angsuran;
use App\Models\Anggota;
use App\Models\Notification;
use Illuminate\Support\Carbon;

class NotifikasiService
{
    public function findByDuplicateCriteria(
        string $anggotaId,
        string $type,
        string $period,
        string $reminderType,
        ?string $referenceId = null
    ): ?Notification {
        $query = Notification::where('anggota_id', $anggotaId)
            ->where('notification_type', $type)
            ->where('notification_period', $period)
            ->where('reminder_type', $reminderType);

        if ($referenceId) {
            $query->where('reference_id', $referenceId);
        }

        return $query->first();
    }

    public function getAdminList(array $filters, int $perPage = 10, ?string $pjUserId = null)
    {
        $query = Notification::with(['anggota.user'])
            ->when($pjUserId, function ($query, $pjUserId) {
                $query->whereHas('anggota', function ($memberQuery) use ($pjUserId) {
                    $memberQuery->where('pj_anggota_id', $pjUserId);
                });
            })
            ->orderBy('scheduled_at', 'desc');

        if (!empty($filters['periode'])) {
            $query->where('notification_period', $filters['periode']);
        }

        if (!empty($filters['notification_type'])) {
            $query->where('notification_type', $filters['notification_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $query->where('is_read', filter_var($filters['is_read'], FILTER_VALIDATE_BOOLEAN));
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

            $notification = Notification::create([
                'anggota_id' => $a->id,
                'title' => 'Pengingat Simpanan Wajib Bulan ' . now()->locale('id')->isoFormat('MMMM YYYY'),
                'message' => 'Simpanan wajib untuk periode ' . now()->locale('id')->isoFormat('MMMM YYYY') . ' jatuh tempo pada ' . $dueDate->locale('id')->translatedFormat('d F Y') . '. Pastikan Anda melakukan setoran sebelum jatuh tempo.',
                'notification_type' => NotificationTypeEnum::MANDATORY_SAVING->value,
                'reference_type' => null,
                'reference_id' => null,
                'notification_period' => $currentPeriod,
                'reminder_type' => $reminderType->value,
                'status' => NotificationStatusEnum::DRAFT->value,
                'is_read' => false,
                'scheduled_at' => now(),
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

            $notification = Notification::create([
                'anggota_id' => $anggotaId,
                'title' => 'Pengingat Angsuran Pembiayaan #' . $a->angsuran_ke,
                'message' => 'Angsuran ke-' . $a->angsuran_ke . ' sebesar Rp ' . number_format($a->nominal_angsuran, 0, ',', '.') . ' jatuh tempo pada ' . $dueDate->locale('id')->translatedFormat('d F Y') . '.',
                'notification_type' => NotificationTypeEnum::INSTALLMENT->value,
                'reference_type' => Angsuran::class,
                'reference_id' => $a->id,
                'notification_period' => $period,
                'reminder_type' => $reminderType->value,
                'status' => NotificationStatusEnum::DRAFT->value,
                'is_read' => false,
                'scheduled_at' => now(),
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

    public function deliverNotification(Notification $notification): void
    {
        try {
            $notification->status = NotificationStatusEnum::SENT->value;
            $notification->sent_at = now();
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
        $query = Notification::with('angsuran')
            ->where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->orderBy('scheduled_at', 'desc');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function markAsRead(Notification $notification): void
    {
        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAllAsRead(string $anggotaId): void
    {
        Notification::where('anggota_id', $anggotaId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function markPopupDisplayed(array $ids, string $anggotaId): void
    {
        Notification::where('anggota_id', $anggotaId)
            ->whereIn('id', $ids)
            ->whereNull('alert_displayed_at')
            ->update(['alert_displayed_at' => now()]);
    }

    public function getNotificationDropdown(string $anggotaId): array
    {
        return Notification::where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn(Notification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'is_read' => $notification->is_read,
                'scheduled_at' => $notification->scheduled_at?->format('Y-m-d H:i:s'),
                'href' => route('user.notifications.show', ['notification' => $notification->id]),
            ])
            ->toArray();
    }

    public function getUnreadCount(string $anggotaId): int
    {
        return Notification::where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->where('is_read', false)
            ->count();
    }

    public function getPendingPopupNotifications(string $anggotaId): array
    {
        return Notification::where('anggota_id', $anggotaId)
            ->where('status', NotificationStatusEnum::SENT->value)
            ->where('is_read', false)
            ->whereNull('alert_displayed_at')
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(Notification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
            ])
            ->toArray();
    }
}
