<?php

namespace App\Models;

use App\Models\AmdkProduct;
use App\Models\FinancingItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'contact',
        'address',
        'website_url',
    ];

    public function financingItems()
    {
        return $this->hasMany(FinancingItem::class);
    }

    public function amdkProducts()
    {
        return $this->hasMany(AmdkProduct::class);
    }
}
