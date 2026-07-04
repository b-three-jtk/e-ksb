<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IbadahAccount extends Model
{
    //
    protected $fillable = [
        'target_amount',
        'purpose',
        'akun_simpanan_id',
    ];

    public function akunSimpanan()
    {
        return $this->belongsTo(AkunSimpanan::class);
    }
}
