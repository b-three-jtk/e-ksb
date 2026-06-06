<?php

namespace Database\Factories;

use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition()
    {
        $type = $this->faker->randomElement(array_column(NotificationTypeEnum::cases(), 'value'));
        $reminder = $this->faker->randomElement(array_column(NotificationReminderTypeEnum::cases(), 'value'));

        return [
            'member_id' => Member::factory(),
            'title' => $this->faker->sentence(5),
            'message' => $this->faker->paragraph(),
            'notification_type' => $type,
            'reference_type' => $type === NotificationTypeEnum::INSTALLMENT->value ? 'installment' : null,
            'reference_id' => null,
            'notification_period' => now()->format('Y-m'),
            'reminder_type' => $reminder,
            'status' => NotificationStatusEnum::DRAFT->value,
            'is_read' => false,
            'scheduled_at' => now(),
        ];
    }
}
