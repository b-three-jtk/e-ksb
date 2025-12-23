<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'status',
        'method',
        'description',
        'transaction_date',
        'updated_by',
        'saving_account_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savingAccount()
    {
        return $this->belongsTo(SavingAccount::class);
    }
}
