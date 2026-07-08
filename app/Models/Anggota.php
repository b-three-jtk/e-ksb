<?php

namespace App\Models;

use App\Models\KeuanganAnggota;
use App\Models\Pembiayaan;
use App\Models\AhliWaris;
use App\Models\RekeningAnggota;
use App\Models\DokumenAnggota;
use App\Models\PekerjaanAnggota;
use App\Models\Notifikasi;
use App\Models\AkunSimpanan;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anggota extends Model
{
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'pengguna_id',
        'pj_anggota_id',
        'jenis_kelamin',
        'tempat_lahir',
        'tgl_lahir',
        'status',
        'alamat_domisili',
        'alamat_ktp',
        'status_pernikahan',
        'pendidikan_terakhir',
        'jml_tanggungan',
        'tgl_pengunduran_diri',
    ];

    // Simpanan
    public function akunSimpanan()
    {
        return $this->hasMany(AkunSimpanan::class);
    }

    // Detail Anggota
    public function user()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function penanggungJawab()
    {
        return $this->belongsTo(Pengguna::class, 'pj_anggota_id');
    }

    public function keuanganAnggota()
    {
        return $this->hasOne(KeuanganAnggota::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(RekeningAnggota::class);
    }

    public function ahliWaris()
    {
        return $this->belongsToMany(AhliWaris::class, 'anggota_ahli_waris', 'anggota_id', 'nik_ahli_waris')
                    ->withPivot('hubungan')
                    ->withTimestamps();
    }

    public function dokumenAnggota()
    {
        return $this->hasMany(DokumenAnggota::class);
    }

    public function pekerjaanAnggota()
    {
        return $this->hasOne(PekerjaanAnggota::class);
    }

    // Murabahah
    public function pembiayaan()
    {
        return $this->hasMany(Pembiayaan::class);
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class);
    }
}
