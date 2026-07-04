<?php

namespace App\Models;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberJob extends Model
{
    use HasFactory;
    protected $fillable = [
        'anggota_id',
        'employment_status',
        'job_title',
        'company_or_business_name',
        'business_field',
        'tenure_year',
        'workplace_address',
        'workplace_contact',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }
}
