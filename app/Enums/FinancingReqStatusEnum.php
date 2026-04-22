<?php

namespace App\Enums;

enum FinancingReqStatusEnum: string
{
    case PENDING_REVIEW        = 'Belum Ditinjau';
    case APPROVED              = 'Disetujui';
    case APPROVED_WITH_NOTES   = 'Disetujui Dengan Catatan';
    case REJECTED              = 'Ditolak';
    case WAITING_DOCUMENTS     = 'Menunggu Kelengkapan Dokumen';
    case ACTIVE_INSTALLMENTS = 'Angsuran Berjalan';
    case PAID_EARLY_REQUESTED = 'Permintaan Pelunasan Diajukan';
    case PAID_EARLY = 'Lunas Dipercepat';
    case PAID = 'Lunas';
}
