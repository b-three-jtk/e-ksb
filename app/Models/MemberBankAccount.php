<?php

namespace App\Models;

use App\Models\Member;
use App\Models\SavingTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberBankAccount extends Model
{
    use HasFactory;
    protected $primaryKey = 'account_number';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_number',
        'bank_name',
        'account_name',
        'member_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function savingTransactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }
}
