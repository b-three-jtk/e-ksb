<?php

namespace App\Models;

use App\Models\Pembiayaan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jaminan extends Model
{
    use HasFactory, HasUuids;
    //
    protected $table = 'jaminan';
    protected $fillable = [
        'pembiayaan_id',
        'jenis_jaminan',
        'nama_pemilik',
        'lokasi_kondisi_jaminan',
        'nilai_perkiraan_pasar',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }
}
