<?php

namespace App\Console\Commands;

use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class SendNotificationReminders extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $deskripsi = 'Generate and send mandatory saving and angsuran reminders daily.';

    public function __construct(private NotifikasiService $notificationService)
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
