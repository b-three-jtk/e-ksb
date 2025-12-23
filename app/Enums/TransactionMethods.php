<?php

namespace App\Enums;

enum TransactionMethods: string
{
    case CASH = 'Tunai';
    case CASHLESS = 'Non-Tunai';
}
