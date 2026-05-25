<?php

namespace App\Models;

use App\Models\Financing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenor',
        'due_day',
        'financing_id',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function payment()
    {
        return $this->hasMany(InstallmentPaymentTransaction::class);
    }
}
