<?php

namespace App\Models;

use App\Models\Financing;
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
        'financing_id',
        'jenis_barang_id'
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
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
