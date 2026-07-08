<?php

namespace App\Models;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanUmum extends Model
{
    use HasFactory;
    protected $table = 'pengaturan_umum';
    protected $fillable = [
        'key',
        'value',
        'tgl_diberlakukan',
        'deskripsi',
        'updated_by',
    ];

    protected $casts = [
        'tgl_diberlakukan' => 'date',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(Pengguna::class, 'updated_by');
    }
}
