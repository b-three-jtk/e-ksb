<?php

namespace App\Models;

use App\Models\Financial;
use App\Models\Financing;
use App\Models\Heir;
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
        'resignation_date',
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

    public function financials()
    {
        return $this->hasOne(Financial::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(MemberBankAccount::class);
    }

    public function heirs()
    {
        return $this->belongsToMany(Heir::class, 'member_heirs', 'anggota_id', 'heir_nik')
                    ->withPivot('relationship')
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
    public function financings()
    {
        return $this->hasMany(Financing::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
