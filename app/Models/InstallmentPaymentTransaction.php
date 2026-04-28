<?php

namespace App\Models;

use App\Models\InstallmentPaymentSchedule;
use App\Models\PointTransaction;
use App\Models\User;
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
        'margin_paid', // margin_amount / tenor
        'payment_date',
        'schedule_id',
        'updated_by',
        'installment_payment_receipt'
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

    public function point()
    {
        return $this->belongsTo(PointTransaction::class, 'point_id');
    }
}
