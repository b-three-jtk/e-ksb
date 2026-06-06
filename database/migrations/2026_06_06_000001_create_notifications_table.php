<?php

use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('message');
            $table->enum('notification_type', array_column(NotificationTypeEnum::cases(), 'value'));
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('notification_period', 7);
            $table->enum('reminder_type', array_column(NotificationReminderTypeEnum::cases(), 'value'));
            $table->enum('status', array_column(NotificationStatusEnum::cases(), 'value'))->default(NotificationStatusEnum::DRAFT->value);
            $table->boolean('is_read')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('alert_displayed_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'notification_type', 'notification_period', 'reminder_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
