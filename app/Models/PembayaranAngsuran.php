<?php

namespace App\Models;

use App\Models\Pengguna;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranAngsuran extends Model
{
    use HasUuids, HasFactory, Auditable;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'pembayaran_angsuran';
    protected $fillable = [
        'kode_transaksi_pembayaran',
        'metode_pembayaran',
        'is_pelunasan_lebih_cepat',
        'jumlah_angsuran_dibayar',
        'pokok_dibayar',
        'margin_dibayar',
        'tgl_pembayaran',
        'struk_pembayaran',
        'bukti_pembayaran',

        'no_rekening',
        'angsuran_id',
        'updated_by',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'tgl_pembayaran' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function angsuran()
    {
        return $this->belongsTo(Angsuran::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }
}
