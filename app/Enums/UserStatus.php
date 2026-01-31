<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'Aktif';
    case INACTIVE = 'Tidak Aktif';
    case INREVIEW = 'Dalam Peninjauan';
    case PENDING = 'Menunggu Pembayaran';
    case REJECTED = 'Ditolak dengan alasan';
    case RESIGNED = 'Mengundurkan Diri';
    case RESIGNED_REQUESTED = 'Pengunduran Diri Diajukan';
    case RESIGNED_REJECTED = 'Pengunduran Diri Ditolak';
}
