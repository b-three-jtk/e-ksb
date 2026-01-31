<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Loan extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'total_price',
        'tenor',
        'amount_ins',
        'financing_id',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }
}
