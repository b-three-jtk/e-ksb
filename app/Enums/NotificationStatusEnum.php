<?php

namespace App\Enums;

enum NotificationStatusEnum: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case FAILED = 'failed';
}
