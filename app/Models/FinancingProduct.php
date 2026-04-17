<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingProduct extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'request_description',
        'qty',
        'condition',
        'cost_price',
        'margin_amount',
        'product_id',
        'purchase_receipt',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }
}
