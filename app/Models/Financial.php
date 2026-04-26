<?php

namespace App\Models;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financial extends Model
{
    use HasFactory;
    protected $fillable = [
        'member_id',
        'financial_type',
        'amount',
        'category',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
