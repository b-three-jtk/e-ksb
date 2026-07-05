<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifikasiPembiayaan extends Model
{
    protected $table = 'verifikasi_pembiayaan';

    protected $fillable = [
        'pembiayaan_id',
        'keputusan_akhir',
        'catatan',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    protected $casts = [
        'diverifikasi_pada' => 'datetime',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }

    public function verifikator()
    {
        return $this->belongsTo(Pengguna::class, 'diverifikasi_oleh');
    }
}
