<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case WITHDRAWAL = 'Penarikan';
    case DEPOSIT = 'Penyetoran';
}
