<?php

namespace App\Models;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberJob extends Model
{
    use HasFactory;
    protected $fillable = [
        'member_id',
        'job_title',
        'company_or_business_name',
        'business_field',
        'tenure_year',
        'workplace_address',
        'workplace_contact',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
