<?php

namespace App\Models;

use App\Models\Member;
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
        'heir_contact',
    ];

    public function member()
    {
        return $this->belongsToMany(Member::class);
    }
}
