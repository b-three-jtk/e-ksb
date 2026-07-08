<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AnggotaAhliWaris extends Pivot
{
    protected $table = 'anggota_ahli_waris';
    protected $fillable = [
        'hubungan'
    ];
}
