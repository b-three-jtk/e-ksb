<?php

namespace App\Enums;

enum AhliWarisEnum: string
{
    case FATHER = 'Ayah';
    case MOTHER = 'Ibu';

    case SON = 'Anak Laki-laki';
    case DAUGHTER = 'Anak Perempuan';

    case BROTHER = 'Saudara Laki-laki';
    case SISTER = 'Saudara Perempuan';

    case UNCLE = 'Paman';

    case GRANDFATHER = 'Kakek';
    case GRANDMOTHER = 'Nenek';

    case HUSBAND = 'Suami';
    case WIFE = 'Istri';
}
