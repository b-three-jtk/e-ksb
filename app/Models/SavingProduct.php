<?php

namespace App\Models;

use App\Models\SavingAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'due_date',
    ];

    public function savingAccounts()
    {
        return $this->hasMany(SavingAccount::class);
    }
}
