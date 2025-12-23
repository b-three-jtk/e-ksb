<?php

namespace App\Enums;

enum TransactionType: string
{
    case WITHDRAWAL = 'Penarikan';
    case DEPOSIT = 'Penyetoran';
}
