<?php

namespace App\Models;

use App\Models\Financing;
use App\Models\InstallmentPaymentSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;
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
