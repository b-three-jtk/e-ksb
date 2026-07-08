<?php

namespace App\Models;

use App\Models\Anggota;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenAnggota extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'dokumen_anggota';
    
    protected $fillable = [
        'nama_dokumen',
        'lampiran_dokumen',
        'anggota_id',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }
}
