<?php

namespace App\Models;

use App\Models\Collateral;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financing extends Model
{
    use HasUuids, HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'financing_transaction_code',
        'is_wakalah',
        'down_payment',
        'akad_date',
        'notes',
        'paid_date',
        'financing_status',
        'payment_method',
        'signed_akad_document',
        'member_id',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->financing_transaction_code) {
                $model->financing_transaction_code = 'PM' . strtoupper(uniqid());
            }
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class);
    }

    // Angsuran
    public function installment()
    {
        return $this->hasOne(Installment::class);
    }

    // Objek Pembiayaan
    public function financingItem()
    {
        return $this->hasOne(FinancingItem::class);
    }

    // Rahn atau Jaminan
    public function collateral()
    {
        return $this->hasOne(Collateral::class);
    }
}
