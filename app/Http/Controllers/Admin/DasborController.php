<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Services\Admin\DasborService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DasborController extends Controller
{
    public function index(Request $req, DasborService $service)
    {
        //  get role spatie
        $role = auth()->user()->getRoleNames()->first();

        $data = [];
        $tanggalAwal = $req->start_date
            ? Carbon::parse($req->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $tanggalAkhir = $req->end_date
            ? Carbon::parse($req->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();
        $filterBy = $req->filter_by ?? 'month';

        [$tanggalAwalSebelumnya, $tanggalAkhirSebelumnya] = $service->getPeriodeSebelumnya($tanggalAwal, $filterBy);

        [$data['total_kas'], $data['total_kas_persen']] = $service->getTotalKas($tanggalAkhir, $tanggalAkhirSebelumnya);

        [$data['total_anggota_aktif'], $data['total_anggota_aktif_persen']] = $service->getTotalAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, UserStatusEnum::ACTIVE->value);

        [$data['total_anggota_non_aktif'], $data['total_anggota_non_aktif_persen']] = $service->getTotalAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, UserStatusEnum::INACTIVE->value);

        [$data['total_pengurus'], $data['total_pengurus_persen']] = $service->getTotalPengurus($tanggalAkhir, $tanggalAkhirSebelumnya);

        $data['rasio_kas'] = $service->getRasioKas($tanggalAkhir);

        $data['rasio_fdr'] = $service->getRasioFDR($tanggalAkhir);

        [$data['total_simpanan_masuk'], $data['total_simpanan_masuk_persen']] = $service->getTotalSimpanan($tanggalAkhir, $tanggalAkhirSebelumnya, 'Credit');

        [$data['total_simpanan_keluar'], $data['total_simpanan_keluar_persen']] = $service->getTotalSimpanan($tanggalAkhir, $tanggalAkhirSebelumnya, 'Debit');

        $data['total_angsuran_belum_lunas'] = $service->getTotalAngsuranBelumLunas();

        [$data['total_pembiayaan_tersalurkan'], $data['total_pembiayaan_tersalurkan_persen']] = $service->getTotalPembiayaanTersalurkan($tanggalAkhir, $tanggalAkhirSebelumnya);

        [$data['modal_sudah_dialokasi'], $data['modal_sudah_dialokasi_persen']] = $service->getTotalModalSudahDialokasi($tanggalAkhir, $tanggalAkhirSebelumnya);

        [$data['total_pembiayaan_aktif'], $data['total_pembiayaan_aktif_persen']] = $service->getTotalPembiayaanAktif($tanggalAkhir, $tanggalAkhirSebelumnya);

        [$data['total_permohonan_pembiayaan'], $data['total_permohonan_pembiayaan_persen']] = $service->getTotalPermohonanPembiayaan($tanggalAkhir, $tanggalAkhirSebelumnya);

        [$data['total_simpanan_anggota_masuk'], $data['total_simpanan_anggota_masuk_persen']] = $service->getTotalSimpananAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, 'Penyetoran');

        [$data['total_simpanan_anggota_keluar'], $data['total_simpanan_anggota_keluar_persen']] = $service->getTotalSimpananAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, 'Penarikan');

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
                'total_simpanan_keluar_persen' => $data['total_simpanan_keluar_persen'],
                'total_angsuran_belum_lunas' => $data['total_angsuran_belum_lunas'],
                'total_pembiayaan_tersalurkan' => $data['total_pembiayaan_tersalurkan'],
                'modal_sudah_dialokasi' => $data['modal_sudah_dialokasi'],
                'modal_sudah_dialokasi_persen' => $data['modal_sudah_dialokasi_persen'],
                'total_pembiayaan_aktif' => $data['total_pembiayaan_aktif'],
                'total_pembiayaan_aktif_persen' => $data['total_pembiayaan_aktif_persen'],
                'rasio_kas' => $data['rasio_kas'],
                'rasio_fdr' => $data['rasio_fdr'],
                'total_simpanan_anggota_masuk' => $data['total_simpanan_anggota_masuk'],
                'total_simpanan_anggota_masuk_persen' => $data['total_simpanan_anggota_masuk_persen'],
                'total_simpanan_anggota_keluar' => $data['total_simpanan_anggota_keluar'],
                'total_simpanan_anggota_keluar_persen' => $data['total_simpanan_anggota_keluar_persen'],
            ],
                'pertumbuhan_pendapatan' => Inertia::lazy(fn() => $service->getPendapatanPerPeriode($req->start_date, $req->end_date, $filterBy)),
                'pertumbuhan_anggota' => Inertia::lazy(fn() => $service->getTotalAnggotaPerPeriode($tanggalAwal, $tanggalAkhir, $filterBy)),
                'peta_simpanan' => Inertia::lazy(fn() => $service->getPetaSimpanan($tanggalAkhir, $req->savings_filter ?? 'jenis')),
                'peta_pembiayaan' => Inertia::lazy(fn() => $service->getPetaPembiayaan($tanggalAkhir)),
                'transaksi_terbaru' => Inertia::lazy(fn() => $service->getTransaksiTerbaru($req->transaction_filter ?? 'all', $role)),
                'jatuh_tempo_terdekat' => Inertia::lazy(fn() => $service->getJatuhTempoTerdekat($req->nearest_filter ?? 'all')),
                'permohonan_murabahah' => Inertia::lazy(fn() => $service->getPermohonanMurabahahTerbaru($tanggalAwal, $tanggalAkhir)),
                'pembayaran_terlambat' => Inertia::lazy(fn() => $service->getPembayaranTerlambat($tanggalAkhir)),
                'transaksi_simpanan_terbaru' => Inertia::lazy(fn() => $service->getTransaksiSimpananTerbaru($tanggalAkhir, $req->saving_transaction_filter ?? 'all')),
        ]);
    }
}
