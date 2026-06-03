<?php

namespace App\Models;

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
        'payment_method',
        'is_early_repayment',
        'nominal',
        'payment_date',
        'installment_payment_receipt',

        'installment_id',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
