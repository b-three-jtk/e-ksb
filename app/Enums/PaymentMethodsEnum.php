<?php

namespace App\Enums;

enum PaymentMethodsEnum: string
{
    case CASH = 'Tunai';
    case CASHLESS = 'Non-Tunai';
}
