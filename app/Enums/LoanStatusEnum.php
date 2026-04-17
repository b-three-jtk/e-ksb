<?php

namespace App\Enums;

enum LoanStatusEnum: string
{
    case BORROWED = 'Dipinjam';
    case RETURNED = 'Dikembalikan';
}
