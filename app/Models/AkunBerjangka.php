<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkunBerjangka extends Model
{
    //
    protected $table = "akun_berjangka";
    protected $fillable = [
        'tenor',
        'tujuan',
        'akun_simpanan_id',
    ];

    public function akunSimpanan()
    {
        return $this->belongsTo(AkunSimpanan::class);
    }
}
