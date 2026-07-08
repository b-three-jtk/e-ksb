<?php

namespace App\Models;

use App\Models\Akun;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailJurnal extends Model
{
    use HasFactory;
    protected $table = 'detail_jurnal';
    protected $fillable = [
        'jurnal_id',
        'no_ref_akun',
        'posisi_akun',
        'nominal',
        'updated_by',
    ];

    protected $casts = [];

    public function akun()
    {
        return $this->belongsTo(
            Akun::class,
            'no_ref_akun',
            'no_ref_akun'
        );
    }

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }
}
