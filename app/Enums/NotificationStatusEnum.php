<?php

namespace App\Enums;

enum NotificationStatusEnum: string
{
    case DRAFT = 'draf';
    case SENT = 'terkirim';
    case FAILED = 'gagal_kirim';
}
