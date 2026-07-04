<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerjangkaAccount extends Model
{
    //
    protected $fillable = [
        'tenor',
        'purpose',
        'akun_simpanan_id',
    ];

    public function akunSimpanan()
    {
        return $this->belongsTo(AkunSimpanan::class);
    }
}
