<?php

namespace App\Models;

use App\Models\AmdkProduct;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    //
    protected $fillable = [
        'invoice_id',
        'amdk_product_id',
        'price_per_item',
        'qty',
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceTransaction::class);
    }

    public function amdkProduct()
    {
        return $this->belongsTo(AmdkProduct::class);
    }
}
