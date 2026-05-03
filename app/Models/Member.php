<?php

namespace App\Models;

use App\Models\AmdkTransaction;
use App\Models\Financial;
use App\Models\Financing;
use App\Models\GallonLoan;
use App\Models\Heir;
use App\Models\MemberBankAccount;
use App\Models\MemberDoc;
use App\Models\MemberJob;
use App\Models\SavingAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gender',
        'birth_place',
        'birth_date',
        'status',
        'domicile_address',
        'residential_address',
        'marital_status',
        'spouse_name',
        'last_education',
        'dependents',
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

    public function financials()
    {
        return $this->hasMany(Financial::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(MemberBankAccount::class);
    }

    public function heirs()
    {
        return $this->hasMany(Heir::class);
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

    // AMDK
    public function gallonLoans()
    {
        return $this->hasMany(GallonLoan::class);
    }

    public function amdkTransactions()
    {
        return $this->hasMany(AmdkTransaction::class);
    }
}
