<?php

namespace Database\Factories;

use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notifikasi;
use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notifikasi::class;

    public function definition()
    {
        $type = $this->faker->randomElement(array_column(NotificationTypeEnum::cases(), 'value'));
        $reminder = $this->faker->randomElement(array_column(NotificationReminderTypeEnum::cases(), 'value'));

        return [
            'anggota_id' => Anggota::factory(),
            'title' => $this->faker->sentence(5),
            'message' => $this->faker->paragraph(),
            'notification_type' => $type,
            'jenis_referensi' => $type === NotificationTypeEnum::INSTALLMENT->value ? 'angsuran' : null,
            'reference_id' => null,
            'notification_period' => now()->format('Y-m'),
            'reminder_type' => $reminder,
            'status' => NotificationStatusEnum::DRAFT->value,
            'is_read' => false,
            'scheduled_at' => now(),
        ];
    }
}
