<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PAID = 'Dibayar';
    case LATE = 'Terlambat';
}
