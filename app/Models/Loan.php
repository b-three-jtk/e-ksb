<?php

namespace App\Models;

use App\Models\LoanPaymentSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Loan extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'total_loan',
        'tenor',
        'monthly_installment',
        'remaining_principal',
        'remaining_margin',
        'financing_id',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function paymentSchedules()
    {
        return $this->hasMany(LoanPaymentSchedule::class);
    }
}
