<?php

namespace App\Models;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeuanganAnggota extends Model
{
    use HasFactory;
    protected $table = 'keuangan_anggota';
    protected $fillable = [
        'anggota_id',
        'jml_gaji_pokok',
        'jml_penghasilan_usaha',
        'jml_penghasilan_pasangan',
        'jml_penghasilan_lainnya',
        'jml_biaya_hidup_keluarga',
        'jml_biaya_pendidikan',
        'jml_cicilan',
        'jml_biaya_lainnya',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }
}
