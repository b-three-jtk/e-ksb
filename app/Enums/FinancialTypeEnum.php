<?php

namespace App\Enums;

enum FinancialTypeEnum: string
{
    case BASIC_SALARY_ALLOWANCE = 'Gaji Pokok Dan Tunjangan';
    case NET_BUSINESS_INCOME    = 'Penghasilan Usaha Bersih';
    case SPOUSE_INCOME          = 'Penghasilan Pasangan';
    case OTHER_INCOME           = 'Penghasilan Lainnya';
    case FAMILY_LIVING_COST     = 'Biaya Hidup Keluarga';
    case EDUCATION_COST         = 'Biaya Pendidikan';
    case OTHER_INSTALLMENT      = 'Cicilan/Kredit Lainnya';
    case OTHER_EXPENSE          = 'Biaya Lainnya';
}
