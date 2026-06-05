<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $req, DashboardService $service)
    {
        //  get role spatie
        $role = auth()->user()->getRoleNames()->first();

        $data = [];
        $startDate = $req->start_date
            ? Carbon::parse($req->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = $req->end_date
            ? Carbon::parse($req->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();
        $filterBy = $req->filter_by ?? 'month';

        [$prevStartDate, $prevEndDate] = $service->getPeriodeSebelumnya($startDate, $filterBy);

        [$data['total_kas'], $data['total_kas_persen']] = $service->getTotalKas($endDate, $prevEndDate);

        [$data['total_anggota_aktif'], $data['total_anggota_aktif_persen']] = $service->getTotalAnggota($endDate, $prevEndDate, UserStatusEnum::ACTIVE->value);

        [$data['total_anggota_non_aktif'], $data['total_anggota_non_aktif_persen']] = $service->getTotalAnggota($endDate, $prevEndDate, UserStatusEnum::INACTIVE->value);

        [$data['total_pengurus'], $data['total_pengurus_persen']] = $service->getTotalPengurus($endDate, $prevEndDate);

        $data['rasio_kas'] = $service->getRasioKas($endDate);

        $data['rasio_fdr'] = $service->getRasioFDR($endDate);

        [$data['total_simpanan_masuk'], $data['total_simpanan_masuk_persem']] = $service->getTotalSimpanan($endDate, $prevEndDate, 'Debit');

        [$data['total_simpanan_keluar'], $data['total_simpanan_keluar_persem']] = $service->getTotalSimpanan($endDate, $prevEndDate, 'Credit');

        $data['total_angsuran_belum_lunas'] = $service->getTotalAngsuranBelumLunas();

        $data['total_pembiayaan_tersalurkan'] = $service->getTotalPembiayaanTersalurkan($endDate, $prevEndDate);

        $data['peta_simpanan'] = $service->getPetaSimpanan($endDate, $req->savings_filter ?? 'jenis');

        $data['jatuh_tempo_terdekat'] = $service->getJatuhTempoTerdekat($req->nearest_filter ?? 'all');

        $data['permohonan_murabahah'] = $service->getPermohonanMurabahahTerbaru($startDate, $endDate);

        $data['pembayaran_terlambat'] = $service->getPembayaranTerlambat($endDate);

        $data['transaksi_simpanan_terbaru'] = $service->getTransaksiSimpananTerbaru($endDate, $req->saving_transaction_filter ?? 'all');

        $data['transaksi_terbaru'] = $service->getTransaksiTerbaru($req->transaction_filter ?? 'all');

        $data['pertumbuhan_pendapatan'] = $service->getPendapatanPerPeriode($req->start_date, $req->end_date, $filterBy);

        $data['pertumbuhan_anggota'] = $service->getTotalAnggotaPerPeriode($startDate, $endDate, $filterBy);

        $data['peta_pembiayaan'] = $service->getPetaPembiayaan($endDate);

        return inertia('Admin/Dashboard', [
            'stats' => [
                'total_kas' => $data['total_kas'],
                'total_kas_persen' => $data['total_kas_persen'],
                'total_anggota_aktif' => $data['total_anggota_aktif'],
                'total_anggota_aktif_persen' => $data['total_anggota_aktif_persen'],
                'total_anggota_non_aktif' => $data['total_anggota_non_aktif'],
                'total_anggota_non_aktif_persen' => $data['total_anggota_non_aktif_persen'],
                'total_pengurus' => $data['total_pengurus'],
                'total_pengurus_persen' => $data['total_pengurus_persen'],
                'total_simpanan_masuk' => $data['total_simpanan_masuk'],
                'total_simpanan_masuk_persen' => $data['total_simpanan_masuk_persen'],
                'total_simpanan_keluar' => $data['total_simpanan_keluar'],
                'total_simpanan_keluar_percentage' => $data['total_simpanan_keluar_percentage'],
                'total_angsuran_belum_lunas' => $data['total_angsuran_belum_lunas'],
                'total_pembiayaan_tersalurkan' => $data['total_pembiayaan_tersalurkan'],
                'rasio_kas' => $data['rasio_kas'],
                'rasio_fdr' => $data['rasio_fdr'],
            ],
                'pertumbuhan_pendapatan' => $data['pertumbuhan_pendapatan'],
                'pertumbuhan_anggota' => $data['pertumbuhan_anggota'],
                'peta_simpanan' => $data['peta_simpanan'],
                'peta_pembiayaan' => $data['peta_pembiayaan'],
                'transaksi_terbaru' => $data['transaksi_terbaru'],
                'jatuh_tempo_terdekat' => $data['jatuh_tempo_terdekat'],
                'permohonan_murabahah' => $data['permohonan_murabahah'],
                'pembayaran_terlambat' => $data['pembayaran_terlambat'],
                'transaksi_simpanan_terbaru' => $data['transaksi_simpanan_terbaru'],
        ]);
    }
}
