<?php

namespace App\Repositories;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;

class NotificationRepository
{
    public function query(): Builder
    {
        return Notification::query();
    }

    public function findByDuplicateCriteria(
        string $memberId,
        string $type,
        string $period,
        string $reminderType,
        ?string $referenceId = null
    ): ?Notification {
        $query = Notification::where('member_id', $memberId)
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
        $query = Notification::with(['member.user'])
            ->when($pjUserId, function ($query, $pjUserId) {
                $query->whereHas('member', function ($memberQuery) use ($pjUserId) {
                    $memberQuery->where('pj_user_id', $pjUserId);
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
            $query->whereHas('member.user', function ($q) use ($filters) {
                $q->where('name', 'ILIKE', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
