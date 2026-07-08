<?php

namespace App\Models;

use App\Models\DetailJurnal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Akun extends Model
{
    use HasFactory;
    protected $primaryKey = 'no_ref_akun';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'akun';
    protected $fillable = [
        'no_ref_akun',
        'nama_akun',
        'kategori_akun',
        'status',
        'saldo',
    ];

    public function detailJurnal()
    {
        return $this->hasMany(DetailJurnal::class, 'account_code', 'no_ref_akun');
    }
}
