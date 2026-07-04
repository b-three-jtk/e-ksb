<?php

namespace App\Models;

use App\Models\SavingTransaction;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poin extends Model
{
    use HasFactory;

    protected $table = 'poin';

    protected $fillable = [
        'jml_perolehan',
        'deskripsi',
        'sisa_tabungan_snapshot',
        'periode_kalkulasi',
        'pengguna_id',
    ];

    protected function casts(): array
    {
        return [
            'periode_kalkulasi' => 'date',
            'sisa_tabungan_snapshot' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(Pengguna::class);
    }

    public function savingTransactions()
    {
        return $this->hasOne(SavingTransaction::class, 'point_id');
    }
}
