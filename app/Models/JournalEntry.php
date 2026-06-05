<?php

namespace App\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;
    protected $fillable = [
        'no_ref_account',
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
