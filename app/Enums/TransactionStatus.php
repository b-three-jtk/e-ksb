<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'Belum Ditinjau';
    case REJECTED = 'Ditolak dengan alasan';
    case COMPLETED = 'Selesai';
}
