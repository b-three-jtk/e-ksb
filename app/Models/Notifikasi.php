<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';
    protected $fillable = [
        'anggota_id',
        'judul',
        'pesan',
        'jenis_notifikasi',
        'jenis_referensi',
        'referensi_id',
        'periode_notifikasi',
        'jenis_pengingat',
        'status',
        'sudah_dibaca',
        'dijadwalkan_pada',
        'dikirim_pada',
        'dibaca_pada',
        'peringatan_ditampilkan_pada',
    ];

    protected $casts = [
        'sudah_dibaca' => 'boolean',
        'dijadwalkan_pada' => 'datetime',
        'dikirim_pada' => 'datetime',
        'dibaca_pada' => 'datetime',
        'peringatan_ditampilkan_pada' => 'datetime',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }

    public function referensi(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'jenis_referensi', 'referensi_id');
    }

    public function angsuran()
    {
        return $this->belongsTo(Angsuran::class, 'referensi_id');
    }

    public function scopeForMember($query, $anggotaId)
    {
        return $query->where('anggota_id', $anggotaId);
    }

    public function scopeUnread($query)
    {
        return $query->where('sudah_dibaca', false);
    }

    public function scopePendingPopup($query)
    {
        return $query->unread()
            ->whereNull('peringatan_ditampilkan_pada')
            ->where('created_at', '>=', now()->subDay());
    }
}
