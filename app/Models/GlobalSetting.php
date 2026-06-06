<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'effective_date',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
