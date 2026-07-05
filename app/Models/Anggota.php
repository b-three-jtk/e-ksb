<?php

namespace App\Models;

use App\Models\KeuanganAnggota;
use App\Models\Pembiayaan;
use App\Models\AhliWaris;
use App\Models\MemberBankAccount;
use App\Models\MemberDoc;
use App\Models\MemberJob;
use App\Models\Notification;
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
        return $this->hasMany(MemberBankAccount::class);
    }

    public function ahliWaris()
    {
        return $this->belongsToMany(AhliWaris::class, 'anggota_ahli_waris', 'anggota_id', 'nik_ahli_waris')
                    ->withPivot('hubungan')
                    ->withTimestamps();
    }

    public function memberDocs()
    {
        return $this->hasMany(MemberDoc::class);
    }

    public function memberJobs()
    {
        return $this->hasOne(MemberJob::class);
    }

    // Murabahah
    public function pembiayaan()
    {
        return $this->hasMany(Pembiayaan::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
