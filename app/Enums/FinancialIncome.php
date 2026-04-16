<?php

namespace App\Enums;

enum FinancialIncome: string
{
    case BASIC_SALARY_ALLOWANCE = 'Gaji Pokok Dan Tunjangan';
    case NET_BUSINESS_INCOME    = 'Penghasilan Usaha Bersih';
    case SPOUSE_INCOME          = 'Penghasilan Pasangan';
    case OTHER_INCOME           = 'Penghasilan Lainnya';
}
