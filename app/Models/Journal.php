<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Journal extends Model
{
    use HasUuids;

    protected $fillable = [
        'tgl_transaksi',
        'created_by',
    ];

    public function entries()
    {
        return $this->hasMany(JournalEntry::class, 'journal_id');
    }
}