<?php

namespace App\Models;

use App\Models\LoanPaymentSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Installment extends Model
{
    protected $fillable = [
        'tenor',
        'financing_id',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function paymentSchedules()
    {
        return $this->hasMany(InstallmentPaymentSchedule::class);
    }
}
