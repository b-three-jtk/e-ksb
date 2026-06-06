<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendNotificationReminders extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'Generate and send mandatory saving and installment reminders daily.';

    public function __construct(private NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->notificationService->sendReminderNotifications();

        $this->info('Notification reminder process complete.');

        return self::SUCCESS;
    }
}
