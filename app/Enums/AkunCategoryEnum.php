<?php

namespace App\Enums;

enum AkunCategoryEnum: string
{
    case ASSET = 'Aset';
    case LIABILITY = 'Liabilitas';
    case EQUITY = 'Ekuitas';
    case REVENUE = 'Pendapatan';
    case EXPENSE = 'Beban';
}
