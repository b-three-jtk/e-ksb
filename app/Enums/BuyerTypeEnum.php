<?php

namespace App\Enums;

enum BuyerTypeEnum: string
{
    case KSB = 'KSB';
    case NONMEMBER = 'Non-Member';
    case MEMBER = 'Member';
}
