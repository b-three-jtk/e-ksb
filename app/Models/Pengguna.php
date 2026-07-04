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

class Pengguna extends Authenticatable
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

    protected $table = 'pengguna';

    protected $fillable = [
        'foto_profil',
        'kode_pengguna',
        'nik',
        'nama',
        'email',
        'no_telp',
        'tgl_bergabung',
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
            'tgl_bergabung' => 'date',
        ];
    }

    /**
     * Get the profile picture URL
     */
    public function getProfilePictureUrlAttribute()
    {
        if ($this->foto_profil) {
            return asset('storage/' . $this->foto_profil);
        }
        return asset('images/default-avatar.png');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->kode_pengguna) {
                $last = Pengguna::max('kode_pengguna');
                $lastNumber = $last ? (int) substr($last, -4) : 0;
                $model->kode_pengguna = 'KSB' . date('ym') . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }
        });
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
        return $this->hasMany(Member::class, 'pj_anggota_id');
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
