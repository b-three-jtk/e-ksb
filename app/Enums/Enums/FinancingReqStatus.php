<?php

namespace App\Enums\Enums;

enum FinancingReqStatus: string
{
    case PENDING_REVIEW        = 'Belum Ditinjau';
    case APPROVED              = 'Disetujui';
    case APPROVED_WITH_NOTES   = 'Disetujui Dengan Catatan';
    case REJECTED              = 'Ditolak';
    case WAITING_DOCUMENTS     = 'Menunggu Kelengkapan Dokumen';
    case ITEM_RECEIVED         = 'Barang Diterima';
}
