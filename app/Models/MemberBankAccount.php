<?php

namespace App\Models;

use App\Models\Anggota;
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
        'anggota_id',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function savingTransactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }
}
