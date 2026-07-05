<?php

namespace App\Models;

use App\Models\Pembiayaan;
use App\Models\JenisBarang;
use App\Models\Pemasok;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjekPembiayaan extends Model
{
    use HasFactory;
    //

    protected $table = 'objek_pembiayaan';
    protected $fillable = [
        'nama_barang',
        'spesifikasi_barang',
        'kuantitas',
        'kondisi_produk',
        'harga_beli_per_unit',
        'struk_pembelian',
        
        'pemasok_id',
        'pembiayaan_id',
        'jenis_barang_id'
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class);
    }

    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class);
    }
}
