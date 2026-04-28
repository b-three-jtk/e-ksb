<?php

namespace App\Models;

use App\Models\Financing;
use App\Models\FinancingProduct;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingItem extends Model
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
        'supplier_id',
        'financing_id',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function product()
    {
        return $this->belongsTo(FinancingProduct::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
