<?php

namespace App\Models;

use App\Models\Financing;
use App\Models\InstallmentPaymentTransaction;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\SavingTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'profile_picture',
        'user_code',
        'nik',
        'name',
        'email',
        'phone_number',
        'joined_date',
        'status',
        'password',
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
            'joined_date' => 'date',
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

    // universal relation
    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function globalSettings()
    {
        return $this->hasMany(GlobalSetting::class, 'updated_by');
    }

    // Is-a
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function allocatedMembers()
    {
        return $this->hasMany(Member::class, 'pj_user_id');
    }

    // Verifies if the user has a specific role
    public function financing()
    {
        return $this->hasMany(Financing::class);
    }

    public function savingTransactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }

    public function installmentPayments()
    {
        return $this->hasMany(InstallmentPaymentTransaction::class);
    }
}
