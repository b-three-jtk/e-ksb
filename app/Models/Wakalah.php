<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wakalah extends Model
{
    //

    protected $table = 'wakalah';
    protected $fillable = [
        'akad_date',
        'signed_akad_document',

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
