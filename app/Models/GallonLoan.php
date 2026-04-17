<?php

namespace App\Models;

use App\Models\AmdkProduct;
use Illuminate\Database\Eloquent\Model;

class GallonLoan extends Model
{
    //
    protected $fillable = [
        'amdk_product_id',
        'user_id',
        'return_date',
        'loan_status',

        'updated_by',
    ];

    public function amdkProduct()
    {
        return $this->belongsTo(AmdkProduct::class, 'amdk_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
