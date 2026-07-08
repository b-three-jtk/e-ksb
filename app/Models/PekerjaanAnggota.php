<?php

namespace App\Models;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PekerjaanAnggota extends Model
{
    use HasFactory;
    protected $table = 'pekerjaan_anggota';
    protected $fillable = [
        'anggota_id',
        'status_pekerjaan',
        'jabatan_pekerjaan',
        'nama_perusahaan',
        'bidang_usaha',
        'lama_bekerja',
        'alamat_tempat_bekerja',
        'no_telp_kantor',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }
}
