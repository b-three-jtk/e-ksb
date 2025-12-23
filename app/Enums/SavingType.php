<?php

namespace App\Enums;

enum SavingType: string
{
    case SIMPANAN_POKOK = 'Simpanan Pokok';
    case SIMPANAN_WAJIB = 'Simpanan Wajib';
    case SIMPANAN_SUKARELA = 'Simpanan Sukarela';
}
