<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPaymentSchedule extends Model
{
    protected $fillable = [
        'due_date',
        'installment_number',
        'installment_schedule_status',
        'installment_id',
    ];

    public function installment()
    {
        return $this->belongsTo(Installment::class, 'installment_id', 'id');
    }

    public function payment()
    {
        return $this->hasOne(InstallmentPaymentTransaction::class);
    }
}
