<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IbadahAccount extends Model
{
    //
    protected $fillable = [
        'tenor',
        'target_amount',
        'saving_account_id',
    ];

    public function savingAccount()
    {
        return $this->belongsTo(SavingAccount::class);
    }
}
