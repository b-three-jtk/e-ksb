<?php

namespace App\Enums;

enum HeirEnum: string
{
    case CHILD = 'Anak';
    case HUSBAND = 'Suami';
    case WIFE = 'Istri';
    case COUSIN = 'Sepupu';
    case SIBLING = 'Saudara';
}
