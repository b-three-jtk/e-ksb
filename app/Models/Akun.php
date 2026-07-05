<?php

namespace App\Models;

use App\Models\JournalEntry;
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

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'account_code', 'no_ref_akun');
    }
}
