<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SavingTransactionDoc extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
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
