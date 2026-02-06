<?php

namespace App\Models;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Database\Eloquent\Model;

class LoanPaymentSchedule extends Model
{
    protected $fillable = [
        'total_amount',
        'principal_amount',
        'margin_amount',
        'due_date',
        'installment_number',
        'status',
        'loan_id',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'id');
    }

    public function payment()
    {
        return $this->hasOne(LoanPayment::class);
    }
}
