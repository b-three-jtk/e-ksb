<?php

namespace App\Enums;

enum FinancingPaymentMethodEnum: string
{
    case CASH = 'Tunai';
    case INSTALLMENT = 'Cicilan';
    case TANGGUH = 'Tangguh';
}
