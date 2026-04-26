<?php

namespace App\Models;

use App\Models\AmdkProduct;
use App\Models\AmdkTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmdkTransactionItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'amdk_product_id',
        'price_per_item',
        'qty',
    ];

    public function invoice()
    {
        return $this->belongsTo(AmdkTransaction::class);
    }

    public function amdkProduct()
    {
        return $this->belongsTo(AmdkProduct::class);
    }
}
