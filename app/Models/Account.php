<?php

namespace App\Models;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $primaryKey = 'no_ref_account';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'no_ref_account',
        'account_name',
        'account_category',
        'status',
    ];

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'account_code', 'no_ref_account');
    }
}
