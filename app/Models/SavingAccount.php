<?php

namespace App\Models;

use App\Models\Member;
use App\Models\SavingTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingAccount extends Model
{
    use HasFactory;
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'saving_account_code',
        'saving_type',
        'balance',
        'member_id',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function transactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }
}
