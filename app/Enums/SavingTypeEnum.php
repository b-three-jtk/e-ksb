<?php

namespace App\Enums;

enum SavingTypeEnum: string
{
    case SIMPANAN_POKOK = 'Simpanan Pokok';
    case SIMPANAN_WAJIB = 'Simpanan Wajib';
    case TABUNGAN_ANGGOTA = 'Tabungan Anggota';
    case TABUNGAN_BERJANGKA = 'Tabungan Berjangka';
    case TABUNGAN_IBADAH = 'Tabungan Ibadah';
}
