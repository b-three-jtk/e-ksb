<?php

namespace App\Models;

use App\Models\Collateral;
use App\Models\FinancingItem;
use App\Models\Installment;
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
    public function installment()
    {
        return $this->hasMany(Installment::class);
    }

    // Objek Pembiayaan
    public function financingItem()
    {
        return $this->hasOne(FinancingItem::class);
    }

    // Rahn atau Jaminan
    public function collateral()
    {
        return $this->hasOne(Collateral::class);
    }

    // Wakalah
    public function wakalah()
    {
        return $this->hasOne(Wakalah::class);
    }

    public function verification()
    {
        return $this->hasMany(FinancingVerification::class);
    }
}
