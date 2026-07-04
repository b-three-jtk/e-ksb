<?php

namespace App\Models;

use App\Models\Pembiayaan;
use App\Models\JenisBarang;
use App\Models\Pemasok;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingItem extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'name',
        'specification',
        'qty',
        'condition',
        'price_per_unit',
        'purchase_receipt',
        
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
