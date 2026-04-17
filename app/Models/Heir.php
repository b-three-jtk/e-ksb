<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Heir extends Model
{
    use HasFactory;

    protected $primaryKey = 'heir_nik';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'heir_nik',
        'heir_name',
        'relationship',
        'heir_contact',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
