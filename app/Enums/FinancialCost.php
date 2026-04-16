<?php

namespace App\Enums;

enum FinancialCost: string
{
    case FAMILY_LIVING_COST     = 'Biaya Hidup Keluarga';
    case EDUCATION_COST         = 'Biaya Pendidikan';
    case OTHER_INSTALLMENT      = 'Cicilan/Kredit Lainnya';
    case OTHER_EXPENSE          = 'Biaya Lainnya';
}
