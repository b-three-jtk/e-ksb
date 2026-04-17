<?php

namespace App\Enums;

enum MaritalStatusEnum: string
{
    case MARRIED = 'Kawin';
    case SINGLE = 'Belum Kawin';
    case DIVORCED_ALIVE = 'Cerai Hidup';
    case DIVORCED_DECEASED = 'Cerai Mati';
}
