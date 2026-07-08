<?php

namespace App\Models;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AhliWaris extends Model
{
    use HasFactory;

    protected $table = 'ahli_waris';
    protected $primaryKey = 'nik_ahli_waris';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nik_ahli_waris',
        'nama_ahli_waris',
        'kontak_ahli_waris',
    ];

    public function anggota()
    {
        return $this->belongsToMany(Anggota::class);
    }
}
