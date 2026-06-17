<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MemberHeir extends Pivot
{
    protected $table = 'member_heirs';
    protected $fillable = [
        'relationship'
    ];
}
