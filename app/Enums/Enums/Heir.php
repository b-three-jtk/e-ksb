<?php

namespace App\Enums\Enums;

enum Heir: string
{
    case CHILD = 'Anak';
    case HUSBAND = 'Suami';
    case WIFE = 'Istri';
    case COUSIN = 'Sepupu';
    case SIBLING = 'Saudara';
}
