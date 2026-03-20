<?php

namespace App\Enums;

enum SavingType: string
{
    case SIMPANAN_POKOK = 'Simpanan Pokok';
    case SIMPANAN_WAJIB = 'Simpanan Wajib';
    case SIMPANAN_SUKARELA = 'Simpanan Sukarela';
    case TABUNGAN_ANGGOTA = 'Tabungan Anggota';
    case TABUNGAN_BERJANGKA = 'Tabungan Berjangka';
    case TABUNGAN_IBADAH = 'Tabungan Ibadah';
    case TABUNGAN_SOSIAL = 'Tabungan Sosial';
}
