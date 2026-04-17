<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case DPS = 'Dewan Pengawas Syariah';
    case PENGAWAS = 'Pengawas';
    case KETUA = 'Ketua';
    case SEKRETARIS = 'Sekretaris';
    case BENDAHARA = 'Bendahara';
    case KETUAMURABAHAH = 'Ketua Murabahah';
    case STAFMURABAHAH = 'Staf Murabahah';
    case KETUAAMDK = 'Ketua AMDK';
    case STOKIST = 'Stokist';
    case PJANGGOTA = 'Penanggung Jawab Anggota';
    case ANGGOTA = 'Anggota';
}
