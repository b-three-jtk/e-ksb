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
        'financing_transaction_code',
        'is_wakalah',
        'down_payment',
        'akad_date',
        'paid_date',
        'financing_status',
        'payment_method',
        'signed_akad_document',
        'user_id',
        'updated_by',
        'financing_product_id'
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

    public function financingProduct()
    {
        return $this->belongsTo(FinancingProduct::class);
    }

    public function collaterals()
    {
        return $this->hasMany(Collateral::class);
    }
}
