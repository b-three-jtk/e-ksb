<?php

namespace App\Models;

use App\Models\Pembiayaan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collateral extends Model
{
    use HasFactory, HasUuids;
    //
    protected $fillable = [
        'pembiayaan_id',
        'collateral_type',
        'owner_name',
        'collateral_location',
        'estimated_market_value',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }
}
