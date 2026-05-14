<?php

namespace App\Models;

use App\Models\Financing;
use App\Models\ProductType;
use App\Models\Supplier;
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
        
        'supplier_id',
        'financing_id',
        'product_type_id'
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
