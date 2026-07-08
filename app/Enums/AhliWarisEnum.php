<?php

namespace App\Enums;

enum AhliWarisEnum: string
{
    case CHILD = 'Anak';
    case HUSBAND = 'Suami';
    case WIFE = 'Istri';
    case COUSIN = 'Sepupu';
    case SIBLING = 'Saudara';
}
