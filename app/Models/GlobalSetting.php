<?php

namespace App\Models;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    use HasFactory;
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
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }
}
