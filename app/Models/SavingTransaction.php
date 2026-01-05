<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SavingTransaction extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
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
