<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory;
    protected $primaryKey = 'account_number';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'account_number',
        'bank_name',
        'account_name',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savingTransactions()
    {
        return $this->hasMany(SavingTransaction::class, 'account_number', 'account_number');
    }
}
