<?php

namespace App\Models;

use App\Models\Financial;
use App\Models\Financing;
use App\Models\Heir;
use App\Models\MemberBankAccount;
use App\Models\MemberDoc;
use App\Models\MemberJob;
use App\Models\Notification;
use App\Models\SavingAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pj_user_id',
        'gender',
        'birth_place',
        'birth_date',
        'status',
        'domicile_address',
        'residential_address',
        'marital_status',
        'last_education',
        'dependents',
        'resignation_date',
    ];

    // Simpanan
    public function savingAccounts()
    {
        return $this->hasMany(SavingAccount::class);
    }

    // Detail Member
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penanggungJawab()
    {
        return $this->belongsTo(User::class, 'pj_user_id');
    }

    public function financials()
    {
        return $this->hasOne(Financial::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(MemberBankAccount::class);
    }

    public function heirs()
    {
        return $this->belongsToMany(Heir::class, 'member_heirs', 'member_id', 'heir_nik')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }

    public function memberDocs()
    {
        return $this->hasMany(MemberDoc::class);
    }

    public function memberJobs()
    {
        return $this->hasOne(MemberJob::class);
    }

    // Murabahah
    public function financings()
    {
        return $this->hasMany(Financing::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
