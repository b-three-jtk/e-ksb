<?php

namespace App\Enums;

enum LoanPaymentStatus: string
{
    case PAID = 'Dibayar';
    case REJECTED = 'Ditolak dengan Alasan';
    case PENDING = 'Belum Ditinjau';
}
