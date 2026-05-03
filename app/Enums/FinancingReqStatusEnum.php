<?php

namespace App\Enums;

enum FinancingReqStatusEnum: string
{
    case PENDING_REVIEW        = 'Belum Ditinjau';
    case APPROVED              = 'Disetujui';
    case REJECTED              = 'Ditolak';
    case WAITING_DOCUMENTS     = 'Menunggu Kelengkapan Dokumen';
    case ACTIVE_INSTALLMENTS = 'Angsuran Berjalan';
    case PAID = 'Lunas';
}
