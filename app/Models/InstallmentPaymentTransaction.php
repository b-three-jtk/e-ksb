<?php

namespace App\Models;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPaymentTransaction extends Model
{
    use HasUuids, HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'installment_trans_code',
        'metode_pembayaran',
        'is_early_repayment',
        'nominal',
        'principal_amount',
        'margin_keuntungan',
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
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }
}
