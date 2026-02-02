<?php

namespace App\Models;

use App\Models\LoanPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanRescheduleRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LoanRescheduleRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'loan_payment_id',
        'requested_date',
        'reason',
        'status',
        'validation_notes',
        'checked_by',
    ];

    public function loanPayment()
    {
        return $this->belongsTo(LoanPayment::class);
    }
}
