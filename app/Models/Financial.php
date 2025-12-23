<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Financial extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'financial_type',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
