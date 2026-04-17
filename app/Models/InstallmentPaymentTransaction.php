<?php

namespace App\Models;

use App\Models\InstallmentPaymentSchedule;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InstallmentPaymentTransaction extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'installment_trans_code',
        'installment_payment_method',
        'is_early_repayment',
        'principal_paid',
        'margin_paid',
        'payment_date',
        'schedule_id',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function installmentPaymentSchedule()
    {
        return $this->belongsTo(InstallmentPaymentSchedule::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
