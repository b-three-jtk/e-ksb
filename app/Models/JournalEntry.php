<?php

namespace App\Models;

use App\Models\Akun;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;
    protected $fillable = [
        'journal_group_id',
        'no_ref_akun',
        'position',
        'nominal',
        'updated_by',
        'tgl_transaksi',
    ];

    protected $casts = [
        'tgl_transaksi' => 'date',
    ];

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
