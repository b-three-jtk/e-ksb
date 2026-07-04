<?php

namespace App\Enums;

enum BuyerTypeEnum: string
{
    case KSB = 'KSB';
    case NONMEMBER = 'Non-Anggota';
    case MEMBER = 'Anggota';
}
