<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IbadahAccount extends Model
{
    //
    protected $fillable = [
        'target_amount',
        'purpose',
        'saving_account_id',
    ];

    public function savingAccount()
    {
        return $this->belongsTo(SavingAccount::class);
    }
}
