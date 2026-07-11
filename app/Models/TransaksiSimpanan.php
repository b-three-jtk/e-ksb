<?php

namespace App\Models;

use App\Models\RekeningAnggota;
use App\Models\Poin;
use App\Models\AkunSimpanan;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TransaksiSimpanan extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'transaksi_simpanan';
    protected $fillable = [
        'kode_transaksi_simpanan',
        'nominal_simpanan',
        'saldo_setelah_transaksi',
        'tipe_transaksi',
        'metode_pembayaran_simpanan',
        'deskripsi_simpanan',
        'tgl_transaksi',
        'struk_simpanan',
        'bukti_penyetoran',
        'status',
        'verified_by',
        'verified_at',

        'updated_by',
        'akun_simpanan_id',
        'no_rekening',
        'poin_id',
    ];

    public function akunSimpanan()
    {
        return $this->belongsTo(AkunSimpanan::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Pengguna::class, 'verified_by');
    }

    public function rekeningAnggota()
    {
        return $this->belongsTo(RekeningAnggota::class, 'no_rekening', 'no_rekening');
    }

    public function point()
    {
        return $this->belongsTo(Poin::class, 'poin_id');
    }

    public function notifikasi(): MorphMany
    {
        return $this->morphMany(Notifikasi::class, 'reference');
    }
}
