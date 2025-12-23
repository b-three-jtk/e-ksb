<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case MARRIED = 'Kawin';
    case SINGLE = 'Belum Kawin';
    case DIVORCED_ALIVE = 'Cerai Hidup';
    case DIVORCED_DECEASED = 'Cerai Mati';
}
