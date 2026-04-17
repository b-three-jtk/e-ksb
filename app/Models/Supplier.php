<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'contact',
        'address',
        'website_url',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
