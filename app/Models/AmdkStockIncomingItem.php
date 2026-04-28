<?php

namespace App\Models;

use App\Models\AmdkProduct;
use App\Models\AmdkStockIncoming;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmdkStockIncomingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'amdk_stock_incoming_id',
        'amdk_product_id',
        'quantity',
        'unit_measure',
    ];

    public function stockIncoming()
    {
        return $this->belongsTo(AmdkStockIncoming::class);
    }

    public function product()
    {
        return $this->belongsTo(AmdkProduct::class, 'amdk_product_id');
    }
}
