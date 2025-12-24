<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{

    protected $fillable = [
        'status',
        'method',
        'attachment',
        'loan_id',
        'payment_date',
        'updated_by',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
