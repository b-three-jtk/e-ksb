<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvoiceTransaction extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    //
    protected $fillable = [
        'invoice_number',
        'point_id',
        'user_id',
        'payment_method',
        'buyer_type'
    ];

    public function point()
    {
        return $this->belongsTo(PointTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
