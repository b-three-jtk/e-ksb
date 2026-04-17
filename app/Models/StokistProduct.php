<?php

namespace App\Models;

use App\Models\AmdkProduct;
use Illuminate\Database\Eloquent\Model;

class StokistProduct extends Model
{
    // composite key
    protected $primaryKey = ['stokist_id', 'amdk_product_id'];
    public $incrementing = false;

    protected $fillable = [
        'stokist_id',
        'amdk_product_id',
        'non_member_price',
    ];

    public function stokist()
    {
        return $this->belongsTo(User::class, 'stokist_id');
    }

    public function amdkProduct()
    {
        return $this->belongsTo(AmdkProduct::class, 'amdk_product_id');
    }
}
