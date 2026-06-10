<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancingVerification extends Model
{
    protected $fillable = [
        'financing_id',
        'final_verification_status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
