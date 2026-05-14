<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wakalah extends Model
{
    //
    protected $fillable = [
        'nominal_wakalah',
        'akad_date',

        'financing_id',
        'updated_by',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class);
    }
}
