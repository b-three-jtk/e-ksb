<?php

namespace App\Enums;

enum InstallmentPaymentScheduleStatusEnum: string
{
    case PAID = 'Dibayar';
    case PENDING = 'Menunggu Konfirmasi';
    case CANCELLED = 'Dibatalkan';
    case OVERDUE = 'Terlambat';
    case SCHEDULED = 'Terjadwal';
}
