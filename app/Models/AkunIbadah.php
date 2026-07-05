<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkunIbadah extends Model
{
    //
    protected $table = "akun_ibadah";
    protected $fillable = [
        'target_tabungan',
        'tujuan',
        'akun_simpanan_id',
    ];

    public function akunSimpanan()
    {
        return $this->belongsTo(AkunSimpanan::class);
    }
}
