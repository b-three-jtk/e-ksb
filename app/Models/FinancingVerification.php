<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancingVerification extends Model
{
    protected $fillable = [
        'pembiayaan_id',
        'final_verification_status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }

    public function verifier()
    {
        return $this->belongsTo(Pengguna::class, 'verified_by');
    }
}
