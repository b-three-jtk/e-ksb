<?php

namespace App\Models;

use App\Models\FinancingItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'address',
        'contact'
    ];

    public function financingItems()
    {
        return $this->hasMany(FinancingItem::class);
    }
}
