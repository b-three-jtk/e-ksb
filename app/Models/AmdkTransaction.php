<?php

namespace App\Models;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmdkTransaction extends Model
{
    use HasFactory;
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'invoice_number',
        'point_id',
        'member_id',
        'payment_method',
        'buyer_type',
        'invoice_receipt',
        'stokist_id',
        'updated_by',
    ];

    public function point()
    {
        return $this->belongsTo(PointTransaction::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stokist()
    {
        return $this->belongsTo(User::class, 'stokist_id');
    }
}
