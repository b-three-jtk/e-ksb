<?php

namespace App\Enums;

enum UserStatusEnum: string
{
    case ACTIVE = 'Aktif';
    case INACTIVE = 'Tidak Aktif';
    case RESIGNED_REQUESTED = 'Pengunduran Diri Diajukan';
    case RESIGNED_REJECTED = 'Pengunduran Diri Ditolak';
}
