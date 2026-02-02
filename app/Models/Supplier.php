<?php

namespace App\Models;

use App\Models\Financing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'contact',
        'address',
        'link_address',
    ];

    public function financings()
    {
        return $this->hasMany(Financing::class);
    }
}
