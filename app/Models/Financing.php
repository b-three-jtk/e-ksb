<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financing extends Model
{
    use HasUuids, HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'transaction_code',
        'product_name',
        'product_type',
        'brand',
        'color',
        'condition',
        'description',
        'price',
        'qty',
        'profit',
        'status',
        'isWakalah',
        'down_payment',
        'market_price',
        'supplier_id',
        'updated_by',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loan()
    {
        return $this->hasOne(Loan::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
