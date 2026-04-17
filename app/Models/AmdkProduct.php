<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmdkProduct extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'amdk_product_code',
        'amdk_product_name',
        'stock',
        'unit_measure',
        'purchase_price',
        'stokist_price',
        'member_price',
        'amdk_brand',
        'supplier_id',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
