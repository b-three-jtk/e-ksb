<?php

namespace App\Models;

use App\Models\Financing;
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
        'financing_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
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
