<?php

namespace App\Models;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'receipt_number',
        'transaction_date',
        'description',
        'transaction_receipt',
        'updated_by',
        'source_id',
        'source_type',
    ];

    // Polymorphic relationship to various transaction types
    public function source()
    {
        return $this->morphTo();
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }
}
