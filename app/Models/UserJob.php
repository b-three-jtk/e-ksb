<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserJob extends Model
{
    protected $fillable = [
        'user_id',
        'job_title',
        'company_or_business_name',
        'business_field',
        'tenure_year',
        'business_field',
        'workplace_address',
        'workplace_contact',
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
}
