<?php

namespace App\Models;

use App\Models\MemberBankAccount;
use App\Models\Poin;
use App\Models\AkunSimpanan;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SavingTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'saving_transaction_code',
        'saving_amount',
        'balance_after_transaction',
        'transaction_type',
        'saving_payment_method',
        'saving_description',
        'transaction_date',
        'saving_transaction_receipt',

        'updated_by',
        'akun_simpanan_id',
        'account_number',
        'point_id',
    ];

    public function akunSimpanan()
    {
        return $this->belongsTo(AkunSimpanan::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }

    public function memberBankAccount()
    {
        return $this->belongsTo(MemberBankAccount::class, 'account_number', 'account_number');
    }

    public function point()
    {
        return $this->belongsTo(Poin::class, 'point_id');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'reference');
    }
}
