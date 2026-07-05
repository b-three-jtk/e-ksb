<?php

namespace App\Models;

use App\Models\Pembiayaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Angsuran extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'angsuran';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'tgl_jatuh_tempo',
        'angsuran_ke',
        'nominal_angsuran',
        'status',
        'pembiayaan_id',
    ];

    protected $casts = [
        'tgl_jatuh_tempo' => 'datetime',
    ];

    public function pembiayaan()
    {
        return $this->belongsTo(Pembiayaan::class);
    }

    public function payment()
    {
        return $this->hasOne(InstallmentPaymentTransaction::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'reference');
    }
}
