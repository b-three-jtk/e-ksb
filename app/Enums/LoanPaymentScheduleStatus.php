<?php

namespace App\Enums;

enum LoanPaymentScheduleStatus: string
{
    case PAID = 'Dibayar';
    case PENDING = 'Menunggu Konfirmasi';
    case CANCELLED = 'Dibatalkan';
    case OVERDUE = 'Terlambat';
    case SCHEDULED = 'Terjadwal';
}
