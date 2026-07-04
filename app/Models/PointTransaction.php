<?php

namespace App\Models;

use App\Models\SavingTransaction;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount_earned',
        'activity_description',
        'saving_balance_snapshot',
        'calculation_period',
        'pengguna_id',
    ];

    protected function casts(): array
    {
        return [
            'calculation_period' => 'date',
            'saving_balance_snapshot' => 'decimal:2',
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
