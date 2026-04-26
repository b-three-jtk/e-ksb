<?php

namespace App\Models;

use App\Models\Installment;
use App\Models\InstallmentPaymentTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPaymentSchedule extends Model
{
    use HasFactory;
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
