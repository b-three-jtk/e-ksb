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
        'gaji_pokok_amount',
        'penghasilan_usaha_amount',
        'penghasilan_pasangan_amount',
        'penghasilan_lainnya_amount',
        'biaya_hidup_keluarga_amount',
        'biaya_pendidikan_amount',
        'jumlah_cicilan_amount',
        'jumlah_biaya_lainnya_amount',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
