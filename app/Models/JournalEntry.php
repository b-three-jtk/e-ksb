<?php

namespace App\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'no_ref_account',
        'user_id',
        'position',
        'nominal',

        'updated_by',
        'transaction_date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
