<?php

namespace App\Models;

use App\Models\User;
use App\Models\Financing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancingInspection extends Model
{
    /** @use HasFactory<\Database\Factories\FinancingInspectionFactory> */
    use HasFactory;

    protected $fillable = [
        'financing_id',
        'notes',
        'decision',
        'inspection_by',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspection_by');
    }
}
