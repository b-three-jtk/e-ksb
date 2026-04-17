<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'product_code',
        'product_name',
        'brand',
        'specification',
        'type_id',
        'supplier_id'
    ];

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function financingProducts()
    {
        return $this->hasMany(FinancingProduct::class);
    }
}
