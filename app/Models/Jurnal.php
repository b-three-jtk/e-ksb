<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Jurnal extends Model
{
    use HasUuids;

    protected $table = 'jurnal';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tgl_transaksi',
        'created_by',
    ];

    public function entries()
    {
        return $this->hasMany(JournalEntry::class, 'journal_id');
    }
}