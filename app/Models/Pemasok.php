<?php

namespace App\Models;

use App\Models\ObjekPembiayaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemasok extends Model
{
    use HasFactory;

    protected $table = 'pemasok';
    protected $fillable = [
        'nama_pemasok',
        'alamat_pemasok',
        'kontak_pemasok'
    ];

    public function objekPembiayaan()
    {
        return $this->hasMany(ObjekPembiayaan::class);
    }
}
