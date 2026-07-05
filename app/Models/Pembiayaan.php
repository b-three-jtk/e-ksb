<?php

namespace App\Models;

use App\Models\Jaminan;
use App\Models\ObjekPembiayaan;
use App\Models\Angsuran;
use App\Models\Anggota;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembiayaan extends Model
{
    use HasUuids, HasFactory;

    protected $keyType = 'string';
    protected $table = 'pembiayaan';
    protected $fillable = [
        'kode_pembiayaan',
        'uang_muka',
        'harga_perolehan',
        'margin_keuntungan',
        'tgl_permohonan',
        'tgl_akad',
        'tgl_lunas',
        'status',
        'metode_pembayaran',
        'tenor',
        'dokumen_akad',
        'harga_perkiraan',

        'anggota_id',
        'updated_by',
    ];

    protected $casts = [
        'tgl_akad' => 'datetime',
        'tgl_permohonan' => 'date',
        'tgl_lunas' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->kode_pembiayaan) {
                $model->kode_pembiayaan = 'PM' . strtoupper(uniqid());
            }
        });
    }

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class);
    }

    // Angsuran
    public function angsuran()
    {
        return $this->hasMany(Angsuran::class);
    }

    // Objek Pembiayaan
    public function objekPembiayaan()
    {
        return $this->hasOne(ObjekPembiayaan::class);
    }

    // Rahn atau Jaminan
    public function jaminan()
    {
        return $this->hasOne(Jaminan::class);
    }

    // Wakalah
    public function wakalah()
    {
        return $this->hasOne(Wakalah::class);
    }

    public function verification()
    {
        return $this->hasMany(VerifikasiPembiayaan::class);
    }
}
