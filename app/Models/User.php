<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'member_number',
        'profile_picture',
        'nik',
        'name',
        'birth_place',
        'birth_date',
        'gender',
        'institution',
        'marital_status',
        'address',
        'residential_address',
        'phone_number',
        'last_education',
        'dependents',
        'status',
        'joined_date',
        'member_number',
        'email',
        'password',
        'role_id',
        'work_unit_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the profile picture URL
     */
    public function getProfilePictureUrlAttribute()
    {
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }
        return asset('images/default-avatar.png');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function savingAccounts()
    {
        return $this->hasMany(SavingAccount::class);
    }

    public function financials()
    {
        return $this->hasMany(Financial::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function heirs()
    {
        return $this->hasMany(Heir::class);
    }

    public function userDocs()
    {
        return $this->hasMany(UserDoc::class);
    }

    public function savingTransactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }

    public function financings()
    {
        return $this->hasMany(Financing::class);
    }

    /**
     * Use member_number for route model binding
     */
    public function getRouteKeyName()
    {
        return 'member_number';
    }
}
