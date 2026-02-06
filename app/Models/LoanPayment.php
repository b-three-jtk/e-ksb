<?php

namespace App\Models;

use App\Models\LoanPaymentSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LoanPayment extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'transaction_code',
        'status',
        'method',
        'attachment',
        'amount',
        'principal_paid',
        'margin_paid',
        'is_early_repayment',
        'loan_payment_schedule_id',
        'payment_date',
        'updated_by',
    ];

    public function loanPaymentSchedule()
    {
        return $this->belongsTo(LoanPaymentSchedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
