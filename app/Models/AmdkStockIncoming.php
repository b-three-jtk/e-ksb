<?php

namespace App\Models;

use App\Models\AmdkStockIncomingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmdkStockIncoming extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'notes',
        'incoming_date',
        'receive_receipt',
        'updated_by',
    ];

    protected $casts = [
        'incoming_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(AmdkStockIncomingItem::class);
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
