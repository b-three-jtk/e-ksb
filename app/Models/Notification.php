<?php

namespace App\Models;

use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'title',
        'message',
        'notification_type',
        'reference_type',
        'reference_id',
        'notification_period',
        'reminder_type',
        'status',
        'is_read',
        'scheduled_at',
        'sent_at',
        'read_at',
        'alert_displayed_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'alert_displayed_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class, 'reference_id');
    }

    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopePendingPopup($query)
    {
        return $query->unread()
            ->whereNull('alert_displayed_at')
            ->where('created_at', '>=', now()->subDay());
    }
}
