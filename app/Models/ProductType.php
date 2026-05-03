<?php

namespace App\Models;

use App\Models\FinancingItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'product_type_name',
    ];

    public function financingItems()
    {
        return $this->hasMany(FinancingItem::class);
    }
}
