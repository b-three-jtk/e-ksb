<?php

namespace App\Models;

use App\Models\AmdkTransaction;
use App\Models\InstallmentPaymentTransaction;
use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount_earned',
        'activity_description',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savingTransactions()
    {
        return $this->hasOne(SavingTransaction::class, 'point_id');
    }

    public function installmentPaymentTransactions()
    {
        return $this->hasOne(InstallmentPaymentTransaction::class, 'point_id');
    }

    public function amdkTransactions()
    {
        return $this->hasOne(AmdkTransaction::class, 'point_id');
    }
}
