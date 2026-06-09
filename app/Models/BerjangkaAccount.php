<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerjangkaAccount extends Model
{
    //
    protected $fillable = [
        'tenor',
        'purpose',
        'saving_account_id',
    ];

    public function savingAccount()
    {
        return $this->belongsTo(SavingAccount::class);
    }
}
