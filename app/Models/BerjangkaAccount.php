<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerjangkaAccount extends Model
{
    //
    protected $fillable = [
        'member_id',
        'tenor',
        'objective',
        'saving_account_id',
    ];

    public function savingAccount()
    {
        return $this->belongsTo(SavingAccount::class);
    }
}
