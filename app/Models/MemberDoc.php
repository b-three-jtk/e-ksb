<?php

namespace App\Models;

use App\Models\Member;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDoc extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'doc_name',
        'doc_attachment',
        'member_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
