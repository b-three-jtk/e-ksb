<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collateral extends Model
{
    //
    protected $fillable = [
        'financing_id',
        'collateral_type',
        'collateral_proof',
        'owner_name',
        'collateral_location',
        'estimated_market_value',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }
}
