<?php

namespace App\Models;

use App\Models\Anggota;
use App\Models\TransaksiSimpanan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunSimpanan extends Model
{
    use HasFactory;
    use HasUuids;
    protected $keyType = 'string';
    protected $table = 'akun_simpanan';
    public $incrementing = false;
    protected $fillable = [
        'kode_akun_simpanan',
        'jenis_simpanan',
        'saldo',
        'anggota_id',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransaksiSimpanan::class);
    }

    public function ibadah()
    {
        return $this->hasOne(AkunIbadah::class);
    }

    public function berjangka()
    {
        return $this->hasOne(AkunBerjangka::class);
    }
}
