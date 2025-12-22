<?php

namespace App\Enums\Enums;

enum LoanStatus: string
{
    case PAID = 'Dibayar';
    case LATE = 'Terlambat';
}
