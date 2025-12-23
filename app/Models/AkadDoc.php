<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkadDoc extends Model
{
    protected $fillable = [
        'financing_id',
        'name',
        'attachment',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function financing(): BelongsTo
    {
        return $this->belongsTo(Financing::class);
    }
}
