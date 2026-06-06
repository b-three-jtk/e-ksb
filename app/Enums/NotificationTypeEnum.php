<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case MANDATORY_SAVING = 'mandatory_saving';
    case INSTALLMENT = 'installment';
}
