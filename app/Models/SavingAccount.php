<?php

namespace App\Models;

use App\Models\Anggota;
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
        'anggota_id',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }

    public function transactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }

    public function ibadah()
    {
        return $this->hasOne(IbadahAccount::class);
    }

    public function berjangka()
    {
        return $this->hasOne(BerjangkaAccount::class);
    }
}
