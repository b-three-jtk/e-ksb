<?php

namespace App\Models;

use App\Models\FinancingItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisBarang extends Model
{
    use HasFactory;
    //
    protected $table = 'jenis_barang';
    protected $fillable = [
        'nama_jenis_barang',
    ];

    public function financingItems()
    {
        return $this->hasMany(FinancingItem::class);
    }
}
