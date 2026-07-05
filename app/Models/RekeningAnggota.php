<?php

namespace App\Models;

use App\Models\Anggota;
use App\Models\SavingTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekeningAnggota extends Model
{
    use HasFactory;
    protected $primaryKey = 'no_rekening';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'rekening_anggota';

    protected $fillable = [
        'no_rekening',
        'nama_bank',
        'atas_nama',
        'anggota_id',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function savingTransactions()
    {
        return $this->hasMany(SavingTransaction::class);
    }
}
