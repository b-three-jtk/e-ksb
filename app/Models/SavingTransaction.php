<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingTransaction extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'amount',
        'type',
        'status',
        'method',
        'description',
        'transaction_date',
        'updated_by',
        'saving_account_id',
        'account_number',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savingAccount()
    {
        return $this->belongsTo(SavingAccount::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_number', 'account_number');
    }

    public function savingTransactionDoc()
    {
        return $this->hasMany(SavingTransactionDoc::class, 'transaction_id', 'id');
    }
}
