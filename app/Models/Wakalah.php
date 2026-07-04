<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wakalah extends Model
{
    //

    protected $table = 'wakalah';
    protected $fillable = [
        'tgl_akad',
        'dokumen_akad',

        'pembiayaan_id',
        'updated_by',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class);
    }
}
