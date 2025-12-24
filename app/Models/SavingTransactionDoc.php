<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingTransactionDoc extends Model
{
    protected $fillable = [
        'transaction_id',
        'name',
        'attachment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savingTransaction()
    {
        return $this->belongsTo(SavingTransaction::class);
    }

}
