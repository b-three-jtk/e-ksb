<?php

namespace App\Models;

use App\Models\Pembiayaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Installment extends Model
{
    use HasFactory, HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'due_date',
        'installment_no',
        'amount',
        'status',
        'pembiayaan_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }

    public function payment()
    {
        return $this->hasOne(InstallmentPaymentTransaction::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'reference');
    }
}
