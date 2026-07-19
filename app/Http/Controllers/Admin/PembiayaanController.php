<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PositionEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePreFinancingRequest;
use App\Http\Requests\CreateRepaymentRequest;
use App\Http\Requests\StoreFinancingDraftRequest;
use App\Http\Requests\StoreFinancingRequest;
use App\Http\Requests\StoreRekeningRequest;
use App\Models\AhliWaris;
use App\Models\Akun;
use App\Models\AkunSimpanan;
use App\Models\Anggota;
use App\Models\DetailJurnal;
use App\Models\DokumenAnggota;
use App\Models\JenisBarang;
use App\Models\Pemasok;
use App\Models\Pembiayaan;
use App\Models\PengaturanUmum;
use App\Models\Pengguna;
use App\Models\VerifikasiPembiayaan;
use App\Services\Admin\JurnalService;
use App\Services\Admin\PembayaranAngsuranService;
use App\Services\Admin\PembiayaanService;
use App\Services\PembiayaanService as SharedPembiayaanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PembiayaanController extends Controller
{
    public function __construct(
        private PembiayaanService $pembiayaanService,
        private SharedPembiayaanService $sharedPembiayaanService,
        protected PembayaranAngsuranService $pembayaranAngsuranService
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        if ($tab === 'unverified_angsuran' ) {
            if (!$user->can('verify_murabahah') || $user->hasRole(UserRoleEnum::BENDAHARA->value)) {
                abort(403, 'Unauthorized action.');
            }
            $pembiayaan = $this->pembayaranAngsuranService->getUnverifiedAngsuran($search, $perPage, $sortBy, $sortDir);
        } else {
            $pembiayaan = $this->pembiayaanService->getSemuaPembiayaan($search, $tab, $sortBy, $sortDir, $perPage, $user);
        }

        $ringkasanData = [
            ['title' => 'Total Pengajuan Pembiayaan Murabahah','value' => $this->pembiayaanService->getTotalPermohonanPembiayaan(now())],
            ['title' => 'Total Pembiayaan Berlangsung', 'value' => $this->pembiayaanService->getTotalPembiayaanBerlangsung()],
            ['title' => 'Total Modal Belum Diputar', 'value' => $this->pembiayaanService->getModalBelumDiputar()],
        ];

        return inertia('Admin/Financing/Index', [
            'pembiayaan' => $pembiayaan,
            'ringkasan_data' => $ringkasanData,
            'filters' => compact('search', 'perPage', 'tab', 'sortBy', 'sortDir'),
        ]);
    }

    public function show(string $id)
    {
        $pembiayaan = $this->sharedPembiayaanService->getPembiayaanById($id);

        $this->pembiayaanService->computePembiayaanSummary($pembiayaan);
        $this->pembiayaanService->computeNextDueDate($pembiayaan);

        $data = $pembiayaan->toArray();

        $data['angsuran'] = $pembiayaan->angsuran->map(function ($item) {
            return [
                'angsuran_ke'              => $item->angsuran_ke,
                'kode_transaksi_pembayaran'      => $item->payment?->kode_transaksi_pembayaran,
                'tgl_jatuh_tempo'                    => $item->tgl_jatuh_tempo,
                'tgl_pembayaran'               => $item->payment?->tgl_pembayaran,
                'nominal_angsuran'                     => $item->nominal_angsuran,
                'is_pelunasan_lebih_cepat'         => $item->payment?->is_pelunasan_lebih_cepat ?? false,
                'struk_pembayaran' => $item->payment?->struk_pembayaran ? asset('storage/' . $item->payment->struk_pembayaran) : null,
                'payment_id' => $item->payment?->id,
                'status_verifikasi' => $item->payment?->status,
            ];
        });

        return inertia('Admin/Financing/Show', ['data' => $data]);
    }

    public function downloadWakalahAgreement(Request $request, string $id)
    {
        $pembiayaan = Pembiayaan::with(['anggota.user', 'objekPembiayaan', 'wakalah'])->findOrFail($id);

        $wakalahDate = $request->query('date') ?: ($pembiayaan->wakalah?->tgl_akad ?: now());
        // Override tgl_akad for the view
        $pembiayaan->tgl_akad = Carbon::parse($wakalahDate);

        $logoPath = public_path('images/logo/logo-icon.svg');
        $src = '';
        if (file_exists($logoPath)) {
            $data_logo = file_get_contents($logoPath);
            $src = 'data:image/svg+xml;base64,' . base64_encode($data_logo);
        }

        Carbon::setLocale('id');

        $ketuaKoperasi = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Ketua'))->first()->nama ?? '..............................';
        $ketuaMurabahah = Pengguna::role('Ketua Murabahah')->first()->nama ?? '..............................';

        $pdf = Pdf::loadView('exports.wakalah_agreement', compact('pembiayaan', 'src', 'ketuaKoperasi', 'ketuaMurabahah'));

        return $pdf->download('Wakalah_Agreement_' . $pembiayaan->kode_pembiayaan . '.pdf');
    }

    public function downloadMurabahahAgreement(string $id)
    {
        $pembiayaan = Pembiayaan::with(['anggota.user', 'objekPembiayaan', 'objekPembiayaan.jenisBarang', 'jaminan'])->findOrFail($id);

        if (request('tgl_akad')) {
            $pembiayaan->tgl_akad = Carbon::parse(request('tgl_akad'));
        } elseif (!$pembiayaan->tgl_akad) {
            $pembiayaan->tgl_akad = now();
        }

        $logoPath = public_path('images/logo/logo-icon.svg');
        $src = '';
        if (file_exists($logoPath)) {
            $data_logo = file_get_contents($logoPath);
            $src = 'data:image/svg+xml;base64,' . base64_encode($data_logo);
        }

        Carbon::setLocale('id');

        $ketuaKoperasi = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Ketua'))->first()->nama ?? '..............................';

        $hargaPerolehan = (float) $pembiayaan->harga_perolehan;
        $hargaPerkiraan = (float) $pembiayaan->harga_perkiraan;
        $hargaBeliPerUnit = (float) ($pembiayaan->objekPembiayaan->harga_beli_per_unit ?? 0);
        $kuantitas = (int) ($pembiayaan->objekPembiayaan->kuantitas ?? 0);

        $hargaBeli = $hargaPerolehan > 0 ? $hargaPerolehan : ($hargaPerkiraan > 0 ? $hargaPerkiraan : ($hargaBeliPerUnit * $kuantitas));
        
        $margin = (float) request('margin', $pembiayaan->margin_keuntungan ?: 0);
        
        if ($margin == 0 && $hargaBeli > 0) {
            $marginPercent = PengaturanUmum::where('key', 'murabahah_margin_percentage')
                ->where('tgl_diberlakukan', '<=', now())
                ->orderBy('tgl_diberlakukan', 'desc')
                ->first()->value ?? 0;
            $margin = $hargaBeli * ($marginPercent / 100);
        }

        $uangMuka = (float) request('uang_muka', $pembiayaan->uang_muka ?: 0);
        $hargaJual = $hargaBeli + $margin;
        $piutang = $hargaJual - $uangMuka;
        $tenor = (int) request('tenor', $pembiayaan->tenor ?: 1);
        $satuanTenor = request('satuan_tenor', $pembiayaan->satuan_tenor ?: 'Bulan');
        $angsuran = $tenor > 0 ? $piutang / $tenor : 0;
        
        $tglAkad = $pembiayaan->tgl_akad ?? now();
        $noDokumen = $pembiayaan->kode_pembiayaan . '/KSB-MUR/' . Carbon::parse($tglAkad)->format('m') . '/' . Carbon::parse($tglAkad)->format('Y');
        $tanggalJatuhTempo = strtolower($satuanTenor) === 'minggu'
            ? Carbon::parse($tglAkad)->translatedFormat('l')
            : Carbon::parse($tglAkad)->format('d');
            
        $tglLunas = strtolower($satuanTenor) === 'minggu' 
            ? Carbon::parse($tglAkad)->addWeeks($tenor) 
            : Carbon::parse($tglAkad)->addMonths($tenor);

        $kuantitas = $pembiayaan->objekPembiayaan->kuantitas ?: 1;
        $hargaBeliPerUnit = $pembiayaan->objekPembiayaan->harga_beli_per_unit ?: ($hargaBeli / $kuantitas);
        $totalHargaBeli = $kuantitas * $hargaBeliPerUnit;
        
        $namaPemasok = request('nama_pemasok') ?: ($pembiayaan->objekPembiayaan->pemasok->nama_pemasok ?? '..........................................................');
        $alamatPemasok = request('alamat_pemasok') ?: ($pembiayaan->objekPembiayaan->pemasok->alamat_pemasok ?? '..........................................................');

        $pdf = Pdf::loadView('exports.murabahah_agreement', compact(
            'pembiayaan', 'src', 'ketuaKoperasi', 'hargaBeli', 'margin', 'satuanTenor',
            'hargaJual', 'uangMuka', 'piutang', 'tenor', 'angsuran', 'tanggalJatuhTempo',
            'tglLunas', 'kuantitas', 'hargaBeliPerUnit', 'totalHargaBeli', 'namaPemasok', 'alamatPemasok', 'noDokumen'
        ));

        return $pdf->download('Murabahah_Agreement_' . $pembiayaan->kode_pembiayaan . '.pdf');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Financing/Create', [
            'data' => $this->pembiayaanService->getDataOpsi(),
        ]);
    }

    public function loadDraft(string $id)
    {
        $pembiayaan = $this->pembiayaanService->getDraftPembiayaan($id);

        if (!$pembiayaan) {
            throw ValidationException::withMessages(['Data pembiayaan tidak ditemukan atau tidak dalam status yang valid untuk dimuat sebagai draft']);
        }

        return inertia('Admin/Financing/Create', [
            'data' => $this->pembiayaanService->getDataOpsi(),
            'pembiayaan' => [
                'anggota' => $this->pembiayaanService->formatMemberData($pembiayaan->anggota),
                'pembiayaan' => [
                    'id' => $pembiayaan->id,
                    'nama_barang' => $pembiayaan->objekPembiayaan->nama_barang,
                    'jenis_barang_id' => $pembiayaan->objekPembiayaan->jenis_barang_id,
                    'kondisi_produk' => $pembiayaan->objekPembiayaan->kondisi_produk,
                    'kuantitas' => $pembiayaan->objekPembiayaan->kuantitas,
                    'spesifikasi_barang' => $pembiayaan->objekPembiayaan->spesifikasi_barang,
                    'harga_beli_per_unit' => $pembiayaan->objekPembiayaan->harga_beli_per_unit,
                    'harga_perolehan' => $pembiayaan->harga_perolehan,
                    'margin_keuntungan' => $pembiayaan->margin_keuntungan,
                    'pemasok_id' => $pembiayaan->objekPembiayaan->pemasok_id,
                    'uang_muka' => $pembiayaan->uang_muka,
                    'metode_pembayaran' => $pembiayaan->metode_pembayaran,
                    'akad_wakalah_date' => $pembiayaan->wakalah?->tgl_akad,
                    'tgl_akad' => $pembiayaan->tgl_akad,
                    'status' => $pembiayaan->status,
                    'tenor' => $pembiayaan->tenor,
                    'harga_perkiraan' => $pembiayaan->harga_perkiraan,
                    'tangguh_tgl_pembayaran' => $pembiayaan->tangguh_tgl_pembayaran,
                    'is_wakalah' => $pembiayaan->wakalah !== null,
                ],
                'jaminan' => [
                    'jenis_jaminan' => $pembiayaan->jaminan?->jenis_jaminan,
                    'nama_pemilik' => $pembiayaan->jaminan?->nama_pemilik,
                    'nilai_perkiraan_pasar' => $pembiayaan->jaminan?->nilai_perkiraan_pasar,
                    'lokasi_kondisi_jaminan' => $pembiayaan->jaminan?->lokasi_kondisi_jaminan,
                ],
                'verification' => $pembiayaan->verification->map(function ($item) {
                    return [
                        'keputusan_akhir' => $item->keputusan_akhir,
                        'catatan' => $item->catatan,
                        'diverifikasi_oleh_name' => $item->verifikator?->nama,
                        'diverifikasi_pada' => $item->diverifikasi_pada?->format('Y-m-d H:i:s'),
                    ];
                })->sortByDesc('diverifikasi_pada')->values(),
                'documents' => [
                    'family_card' => getDocumentUrl($pembiayaan->anggota->dokumenAnggota->where('nama_dokumen', 'kartu_keluarga')->first()?->lampiran_dokumen),
                    'income_slip' => getDocumentUrl($pembiayaan->anggota->dokumenAnggota->where('nama_dokumen', 'slip_gaji')->first()?->lampiran_dokumen),
                    'bank_book' => getDocumentUrl($pembiayaan->anggota->dokumenAnggota->where('nama_dokumen', 'buku_tabungan')->first()?->lampiran_dokumen),
                    'struk_pembelian' => getDocumentUrl($pembiayaan->objekPembiayaan->struk_pembelian),
                    'akad_document' => getDocumentUrl($pembiayaan->dokumen_akad),
                    'akad_wakalah_document' => getDocumentUrl($pembiayaan->wakalah?->dokumen_akad),
                ],
                'pemasok' => $pembiayaan->objekPembiayaan->pemasok ? [
                    'nama_pemasok' => $pembiayaan->objekPembiayaan->pemasok->nama_pemasok,
                    'alamat_pemasok' => $pembiayaan->objekPembiayaan->pemasok->alamat_pemasok,
                    'kontak_pemasok' => $pembiayaan->objekPembiayaan->pemasok->kontak_pemasok,
                ] : null,
            ],
        ]);
    }

    private function getDocumentUrl($path)
    {
        return $path ? asset('storage/' . $path) : null;
    }

    public function showValidation(string $id)
    {
        $pembiayaan = $this->pembiayaanService->getPembiayaanBelumDireview($id);

        return inertia('Admin/Financing/Validation', [
            'data' => [
                'anggota' => $this->pembiayaanService->formatMemberData($pembiayaan->anggota),
                'margin_percentage' => PengaturanUmum::where('key', 'murabahah_margin_percentage')->where('tgl_diberlakukan', '<=', now())->latest()->first()?->value,
                'tanggal_akhir_periode' => PengaturanUmum::where('key', 'tanggal_akhir_periode')->latest()->first()?->value,
                'pembiayaan' => [
                    'id' => $pembiayaan->id,
                    'kode_pembiayaan' => $pembiayaan->kode_pembiayaan,
                    'nama_barang' => $pembiayaan->objekPembiayaan->nama_barang,
                    'jenis_barang_id' => $pembiayaan->objekPembiayaan->jenis_barang_id,
                    'kondisi_produk' => $pembiayaan->objekPembiayaan->kondisi_produk,
                    'kuantitas' => $pembiayaan->objekPembiayaan->kuantitas,
                    'spesifikasi_barang' => $pembiayaan->objekPembiayaan->spesifikasi_barang,
                    'harga_perolehan' => $pembiayaan->harga_perolehan,
                    'margin_keuntungan' => $pembiayaan->margin_keuntungan,
                    'pemasok_id' => $pembiayaan->objekPembiayaan->pemasok_id,
                    'uang_muka' => $pembiayaan->uang_muka,
                    'metode_pembayaran' => $pembiayaan->metode_pembayaran,
                    'tgl_akad' => $pembiayaan->tgl_akad,
                    'status' => $pembiayaan->status,
                    'jenis_barang' => $pembiayaan->objekPembiayaan->jenisBarang?->nama_jenis_barang,
                    'tenor' => $pembiayaan->tenor,
                    'harga_perkiraan' => $pembiayaan->harga_perkiraan,
                    'tangguh_tgl_pembayaran' => $pembiayaan->tangguh_tgl_pembayaran,
                    'akad_wakalah_date' => $pembiayaan->wakalah?->tgl_akad,
                    'is_wakalah' => $pembiayaan->wakalah !== null,
                ],
                'jaminan' => [
                    'jenis_jaminan' => $pembiayaan->jaminan?->jenis_jaminan,
                    'nama_pemilik' => $pembiayaan->jaminan?->nama_pemilik,
                    'nilai_perkiraan_pasar' => $pembiayaan->jaminan?->nilai_perkiraan_pasar,
                    'lokasi_kondisi_jaminan' => $pembiayaan->jaminan?->lokasi_kondisi_jaminan,
                ],
                'verification' => $pembiayaan->verification->map(function ($item) {
                    return [
                        'keputusan_akhir' => $item->keputusan_akhir,
                        'catatan' => $item->catatan,
                        'diverifikasi_oleh_name' => $item->verifikator?->nama,
                        'diverifikasi_pada' => $item->diverifikasi_pada?->format('Y-m-d H:i:s'),
                    ];
                })->sortByDesc('diverifikasi_pada')->values(),
                'documents' => [
                    'family_card' => $this->getDocumentUrl($pembiayaan->anggota->dokumenAnggota->where('nama_dokumen', 'kartu_keluarga')->first()?->lampiran_dokumen),
                    'income_slip' => $this->getDocumentUrl($pembiayaan->anggota->dokumenAnggota->where('nama_dokumen', 'slip_gaji')->first()?->lampiran_dokumen),
                    'bank_book' => $this->getDocumentUrl($pembiayaan->anggota->dokumenAnggota->where('nama_dokumen', 'buku_tabungan')->first()?->lampiran_dokumen),
                ],
                'pemasok' => $pembiayaan->objekPembiayaan->pemasok ? [
                    'nama_pemasok' => $pembiayaan->objekPembiayaan->pemasok->nama_pemasok,
                    'alamat_pemasok' => $pembiayaan->objekPembiayaan->pemasok->alamat_pemasok,
                    'kontak_pemasok' => $pembiayaan->objekPembiayaan->pemasok->kontak_pemasok,
                ] : null,
            ],
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $id) {
                $pembiayaan = $this->pembiayaanService->getPembiayaanBelumDireview($id);

                if ($validated['status'] === FinancingReqStatusEnum::APPROVED->value) {

                    $danaAlokasi = Akun::where(
                        'nama_akun',
                        'Dana Alokasi Pembiayaan Murabahah'
                    )->firstOrFail();

                    $danaAlokasiMasuk = DetailJurnal::where(
                        'no_ref_akun',
                        $danaAlokasi->no_ref_akun
                    )
                    ->where('posisi_akun', PositionEnum::DEBIT->value)
                    ->sum('nominal');

                    $danaAlokasiKeluar = DetailJurnal::where(
                        'no_ref_akun',
                        $danaAlokasi->no_ref_akun
                    )
                    ->where('posisi_akun', PositionEnum::CREDIT->value)
                    ->sum('nominal');

                    $saldoDanaAlokasi = $danaAlokasiMasuk - $danaAlokasiKeluar;

                    if ($saldoDanaAlokasi < $pembiayaan->harga_perkiraan) {
                        throw ValidationException::withMessages([
                            'status' =>
                                'Dana alokasi pembiayaan tidak mencukupi. Silakan lakukan alokasi dana terlebih dahulu.'
                        ]);
                    }
                }

            $pembiayaan->update([
                'status' => $validated['status'],
            ]);

            VerifikasiPembiayaan::create([
                'pembiayaan_id' => $pembiayaan->id,
                'diverifikasi_oleh' => auth()->id(),
                'keputusan_akhir' => $validated['status'],
                'catatan' => $validated['catatan'] ?? null,
                'diverifikasi_pada' => now(),
            ]);

            if ($validated['status'] === FinancingReqStatusEnum::APPROVED->value) {

                $pembiayaanDalamProses = Akun::where(
                    'nama_akun',
                    'Pembiayaan Dalam Proses'
                )->firstOrFail();

                $danaAlokasi = Akun::where(
                    'nama_akun',
                    'Dana Alokasi Pembiayaan Murabahah'
                )->firstOrFail();

                app(JurnalService::class)->create(
                    [
                        [
                            'akun' => $pembiayaanDalamProses->no_ref_akun,
                            'posisi_akun' => PositionEnum::DEBIT->value,
                            'nominal' => $pembiayaan->harga_perkiraan,
                        ],
                        [
                            'akun' => $danaAlokasi->no_ref_akun,
                            'posisi_akun' => PositionEnum::CREDIT->value,
                            'nominal' => $pembiayaan->harga_perkiraan,
                        ],
                    ],
                    now()->toDateString(),
                    auth()->id()
                );

                // Jurnal uang muka saat approval (semua payment method)
                if ($pembiayaan->uang_muka > 0) {
                    $uangMukaMurabahah = Akun::where(
                        'nama_akun',
                        'Uang Muka Murabahah'
                    )->firstOrFail();

                    $kas = Akun::where(
                        'nama_akun',
                        'Kas'
                    )->firstOrFail();

                    // Penerimaan uang muka dari anggota
                    app(JurnalService::class)->create(
                        [
                            [
                                'akun' => $kas->no_ref_akun,
                                'posisi_akun' => PositionEnum::DEBIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                            [
                                'akun' => $uangMukaMurabahah->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                    );

                    // Offset uang muka ke piutang murabahah
                    $piutangMurabahah = Akun::where(
                        'nama_akun',
                        'Piutang Murabahah'
                    )->firstOrFail();

                    app(JurnalService::class)->create(
                        [
                            [
                                'akun' => $uangMukaMurabahah->no_ref_akun,
                                'posisi_akun' => PositionEnum::DEBIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                            [
                                'akun' => $piutangMurabahah->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                    );
                }
                }
            });

            return redirect()->route('admin.pembiayaan.index')->with('success', 'Keputusan validasi berhasil disimpan');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error validating pembiayaan: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Gagal menyimpan keputusan validasi'
            ]);
        }
    }

    public function store(StorePreFinancingRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = Pengguna::with('anggota.akunSimpanan')
                    ->where('kode_pengguna', $validated['anggota']['kode_pengguna'])
                    ->firstOrFail();

                if ($user->status !== UserStatusEnum::ACTIVE->value) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon harus dalam status aktif']);
                }

                $hasActiveFinancing = $user->anggota->pembiayaan?->whereIn('status', [
                    FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    FinancingReqStatusEnum::TANGGUH->value,
                ])->isNotEmpty();

                if ($hasActiveFinancing) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon masih memiliki pembiayaan yang sedang berjalan']);
                }

                $hasEligibleSaving = AkunSimpanan::where('anggota_id', $user->anggota->id)
                    ->where('jenis_simpanan', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $validated['pembiayaan']['status'] = 'Belum Ditinjau';

                $this->pembiayaanService->syncMemberData($user, $validated['anggota'], $request);
                $this->pembiayaanService->syncFinancingData($user, $request, $validated, auth()->id());
            });

            return redirect()->route('admin.pembiayaan.index')
                ->with('success', 'Permohonan pembiayaan berhasil dikirim');

        } catch (Exception $e) {
            Log::error('Error storing pembiayaan: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    private function getSaldoDanaAlokasi(Akun $danaAlokasi): float
    {
        $masuk = DetailJurnal::where('no_ref_akun', $danaAlokasi->no_ref_akun)
            ->where('posisi_akun', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $keluar = DetailJurnal::where('no_ref_akun', $danaAlokasi->no_ref_akun)
            ->where('posisi_akun', PositionEnum::CREDIT->value)
            ->sum('nominal');

        return $masuk - $keluar;
    }

    public function finalize(StoreFinancingRequest $request)
    {
        try {
            $pembiayaan = DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = Pengguna::with('anggota.akunSimpanan')
                    ->where('kode_pengguna', $validated['anggota']['kode_pengguna'])
                    ->firstOrFail();

                if ($user->status !== UserStatusEnum::ACTIVE->value) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon harus dalam status aktif']);
                }

                $hasEligibleSaving = AkunSimpanan::where('anggota_id', $user->anggota->id)
                    ->where('jenis_simpanan', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $hasActiveFinancing = $user->anggota->pembiayaan?->whereIn('status', [
                    FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    FinancingReqStatusEnum::TANGGUH->value,
                ])->isNotEmpty() ?? false;

                if ($hasActiveFinancing) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses']);
                }

                $this->pembiayaanService->syncMemberData($user, $validated['anggota'], $request);
                $pembiayaan = $this->pembiayaanService->syncFinancingData($user, $request, $validated, auth()->id());

                if (isset($validated['pembiayaan']['tenor']) && $validated['pembiayaan']['metode_pembayaran'] === FinancingPaymentMethodEnum::INSTALLMENT->value) {
                    $this->pembiayaanService->generateInstallments($pembiayaan);
                } else if ($validated['pembiayaan']['metode_pembayaran'] === FinancingPaymentMethodEnum::TANGGUH->value) {
                    $this->pembiayaanService->generateTangguhSchedule($pembiayaan, $validated['pembiayaan']['tangguh_tgl_pembayaran']);
                }

                $pembiayaanDalamProses = Akun::where(
                    'nama_akun',
                    'Pembiayaan Dalam Proses'
                )->firstOrFail();

                $piutangMurabahah = Akun::where(
                    'nama_akun',
                    'Piutang Murabahah'
                )->firstOrFail();

                $pendapatanMargin = Akun::where(
                    'nama_akun',
                    'Pendapatan Margin Murabahah'
                )->firstOrFail();
                $danaAlokasi = Akun::where(
                    'nama_akun',
                    'Dana Alokasi Pembiayaan Murabahah'
                )->firstOrFail();

                $kas = Akun::where(
                    'nama_akun',
                    'Kas'
                )->firstOrFail();

                $costPrice = $pembiayaan->harga_perolehan;
                $margin = $pembiayaan->margin_keuntungan;

                // Kalo pembayaran pembiayaannya cicilan
                if ($pembiayaan->metode_pembayaran === FinancingPaymentMethodEnum::INSTALLMENT->value)
                {
                    $allocatedAmount = $pembiayaan->harga_perkiraan ?? 0;
                    $piutang = $costPrice;
                    $selisih = $allocatedAmount - $piutang;

                    if ($selisih > 0) {

                        app(JurnalService::class)->create(
                            [
                                [
                                    'akun' => $danaAlokasi->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $selisih,
                                ],
                                [
                                    'akun' => $piutangMurabahah->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'akun' => $pembiayaanDalamProses->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $pembiayaan->harga_perkiraan,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } elseif ($selisih == 0){

                        app(JurnalService::class)->create(
                            [
                                [
                                    'akun' => $piutangMurabahah->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'akun' => $pembiayaanDalamProses->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } else {
                        $kelebihan = abs($selisih);
                        $saldoDanaAlokasi = $this->getSaldoDanaAlokasi($danaAlokasi);

                        if ($saldoDanaAlokasi < $kelebihan) {
                            throw ValidationException::withMessages([
                                'harga_perolehan' => 'Harga pokok aktual melebihi dana yang dialokasikan dan saldo dana alokasi tidak mencukupi untuk menutup selisih.'
                            ]);
                        }

                        app(JurnalService::class)->create(
                            [
                                [
                                    'akun' => $piutangMurabahah->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'akun' => $pembiayaanDalamProses->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                                [
                                    'akun' => $danaAlokasi->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $kelebihan,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    }
                }

                // Klo pembayaran Cash
                if ($pembiayaan->metode_pembayaran === FinancingPaymentMethodEnum::CASH->value)
                {
                    $allocatedAmount = $pembiayaan->harga_perkiraan ?? 0;
                    $piutang = $costPrice;
                    $selisih = $allocatedAmount - $piutang;

                    if ($selisih > 0)
                    {
                        app(JurnalService::class)->create(
                        [
                            [
                                'akun' => $danaAlokasi->no_ref_akun,
                                'posisi_akun' => PositionEnum::DEBIT->value,
                                'nominal' => $selisih,
                            ],
                            [
                                'akun' => $kas->no_ref_akun,
                                'posisi_akun' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'akun' => $pembiayaanDalamProses->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'akun' => $pendapatanMargin->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $margin,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                        );
                    } elseif ($selisih == 0)
                    {
                        app(JurnalService::class)->create(
                        [
                            [
                                'akun' => $kas->no_ref_akun,
                                'posisi_akun' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'akun' => $pembiayaanDalamProses->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'akun' => $pendapatanMargin->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $margin,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                        );
                    } else {
                        $kelebihan = abs($selisih);
                        $saldoDanaAlokasi = $this->getSaldoDanaAlokasi($danaAlokasi);

                        if ($saldoDanaAlokasi < $kelebihan) {
                            throw ValidationException::withMessages([
                                'harga_perolehan' => 'Harga pokok aktual melebihi dana yang dialokasikan dan saldo dana alokasi tidak mencukupi untuk menutup selisih.'
                            ]);
                        }

                        app(JurnalService::class)->create(
                        [
                            [
                                'akun' => $kas->no_ref_akun,
                                'posisi_akun' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'akun' => $pembiayaanDalamProses->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'akun' => $danaAlokasi->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $kelebihan,
                            ],
                            [
                                'akun' => $pendapatanMargin->no_ref_akun,
                                'posisi_akun' => PositionEnum::CREDIT->value,
                                'nominal' => $margin,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                        );
                    }

                    // Generate Berita Acara Pelunasan
                    $logoPath = public_path('images/logo/logo-icon.svg');
                    $src = '';
                    if (file_exists($logoPath)) {
                        $data_logo = file_get_contents($logoPath);
                        $src = 'data:image/svg+xml;base64,' . base64_encode($data_logo);
                    }

                    Carbon::setLocale('id');
                    $now = now();
                    $hari = $now->translatedFormat('l');
                    $tanggal = $now->format('d');
                    $bulan = $now->translatedFormat('F');
                    $tahun = $now->format('Y');

                    $transCode = 'LP' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

                    $strukData = [
                        'no_transaksi' => $transCode,
                        'hari' => $hari,
                        'tanggal' => $tanggal,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'no_anggota' => $pembiayaan->anggota->user->kode_pengguna,
                        'nama_anggota' => $pembiayaan->anggota->user->nama,
                        'financing_transaction_code' => $pembiayaan->kode_pembiayaan,
                        'product_name' => $pembiayaan->objekPembiayaan->nama_barang ?? '-',
                        'total_paid_amount' => $costPrice + $margin,
                        'metode' => 'Tunai',
                        'repayment_total' => $costPrice + $margin,
                        'tenor' => $pembiayaan->tenor ?? 0,
                        'satuan_tenor' => $pembiayaan->satuan_tenor,
                        'nama_pengurus' => auth()->user()->nama,
                        'jabatan_pengurus' => auth()->user()->roles->first()->name ?? 'Pengurus',
                        'alamat' => $pembiayaan->anggota->alamat_domisili ?? $pembiayaan->anggota->alamat_ktp ?? '-',
                        'harga_perolehan' => $costPrice,
                        'margin_keuntungan' => $margin,
                        'no_telp' => $pembiayaan->anggota->user->no_telp,
                        'qimah_ismiyyah' => $costPrice + $margin,
                        'qimah_haliyyah' => $costPrice + $margin,
                        'logo' => $src,
                    ];

                    $pdf = Pdf::loadView('exports.repayment_receipt', $strukData);
                    $filePath = 'receipts/repayment/' . $transCode . '.pdf';

                    Storage::disk('public')->put($filePath, $pdf->output());

                    DokumenAnggota::create([
                        'anggota_id' => $pembiayaan->anggota_id,
                        'nama_dokumen' => 'Berita Acara Pelunasan ' . $transCode,
                        'lampiran_dokumen' => $filePath,
                    ]);

                    $pembiayaan->update([
                        'status' => FinancingReqStatusEnum::PAID->value
                    ]);

                    session()->flash('receipt_url', asset('storage/' . $filePath));
                }

                // Klo pembiayaan tangguh
                if ($pembiayaan->metode_pembayaran === FinancingPaymentMethodEnum::TANGGUH->value)
                {
                    $allocatedAmount = $pembiayaan->harga_perkiraan ?? 0;
                    $piutang = $costPrice;
                    $selisih = $allocatedAmount - $piutang;

                    if ($selisih > 0) {
                        app(JurnalService::class)->create(
                            [
                                [
                                    'akun' => $danaAlokasi->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $selisih,
                                ],
                                [
                                    'akun' => $piutangMurabahah->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'akun' => $pembiayaanDalamProses->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } elseif ($selisih == 0) {
                        app(JurnalService::class)->create(
                            [
                                [
                                    'akun' => $piutangMurabahah->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'akun' => $pembiayaanDalamProses->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } else {
                        $kelebihan = abs($selisih);
                        $saldoDanaAlokasi = $this->getSaldoDanaAlokasi($danaAlokasi);

                        if ($saldoDanaAlokasi < $kelebihan) {
                            throw ValidationException::withMessages([
                                'harga_perolehan' => 'Harga pokok aktual melebihi dana yang dialokasikan dan saldo dana alokasi tidak mencukupi untuk menutup selisih.'
                            ]);
                        }

                        app(JurnalService::class)->create(
                            [
                                [
                                    'akun' => $piutangMurabahah->no_ref_akun,
                                    'posisi_akun' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'akun' => $pembiayaanDalamProses->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                                [
                                    'akun' => $danaAlokasi->no_ref_akun,
                                    'posisi_akun' => PositionEnum::CREDIT->value,
                                    'nominal' => $kelebihan,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    }
                }
                return $pembiayaan;
            });

            if ($pembiayaan->metode_pembayaran === FinancingPaymentMethodEnum::CASH->value && session('receipt_url')) {
                return redirect()->route('admin.pembiayaan.pembayaran.success')->with('receipt_data', [
                    'financing_id' => $pembiayaan->id,
                    'struk_pembayaran' => session('receipt_url')
                ]);
            }

            return redirect()->route('admin.pembiayaan.index')
                ->with('success', 'Pembiayaan berhasil difinalisasi');
        } catch (Exception $e) {
            Log::error('Error storing pembiayaan: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    public function saveDraft(StoreFinancingDraftRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = Pengguna::with('anggota.akunSimpanan')
                    ->where('kode_pengguna', $validated['anggota']['kode_pengguna'])
                    ->firstOrFail();

                $this->pembiayaanService->syncMemberData($user, $validated['anggota'], $request);
                $this->pembiayaanService->syncFinancingData($user, $request, $validated, auth()->id());
            });

            return redirect()->route('admin.pembiayaan.index')
                ->with('success', 'Draft berhasil disimpan');

        } catch (Exception $e) {
            Log::error('Error saving draft: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan draft: ' . $e->getMessage()]);
        }
    }

    public function searchMembers(Request $request)
    {
        try {
            $query = $request->input('q');

        $anggota = Anggota::query()
            ->with(['user:id,kode_pengguna,nama,email,nik,no_telp', 'dokumenAnggota', 'keuanganAnggota', 'ahliWaris', 'pekerjaanAnggota', 'pembiayaan:id,anggota_id,status', 'akunSimpanan:id,anggota_id,saldo,created_at'])
            ->whereHas('user', function ($q) use ($query) {
                $q->whereHas('roles', fn($roleQ) => $roleQ->where('name', 'Anggota'))
                    ->where('status', UserStatusEnum::ACTIVE->value)
                    ->where(function ($searchQ) use ($query) {
                        $searchQ->where('nama', 'ILIKE', "%{$query}%")
                            ->orWhere('kode_pengguna', 'ILIKE', "%{$query}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(function (Anggota $anggota) {
                $hasActiveFinancing = $anggota->pembiayaan?->whereIn('status', [
                    FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    FinancingReqStatusEnum::TANGGUH->value,
                ])->isNotEmpty();

                $anggota->is_have_no_obligation = !$hasActiveFinancing;

                $hasEligibleSaving = AkunSimpanan::where('anggota_id', $anggota->id)
                    ->where('jenis_simpanan', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                $anggota->setRelation('ahliWaris', $anggota->ahliWaris->map(function (AhliWaris $ahli_waris) {
                    $ahli_waris->hubungan = $ahli_waris->pivot->hubungan;
                    return $ahli_waris;
                }));

                $anggota->is_have_eligible_saving = $hasEligibleSaving;
                $anggota->family_card = $anggota->dokumenAnggota->where('nama_dokumen', 'kartu_keluarga')->first()?->lampiran_dokumen ? asset('storage/' . $anggota->dokumenAnggota->where('nama_dokumen', 'kartu_keluarga')->first()->lampiran_dokumen) : null;
                $anggota->income_slip = $anggota->dokumenAnggota->where('nama_dokumen', 'slip_gaji')->first()?->lampiran_dokumen ? asset('storage/' . $anggota->dokumenAnggota->where('nama_dokumen', 'slip_gaji')->first()->lampiran_dokumen) : null;
                $anggota->bank_book = $anggota->dokumenAnggota->where('nama_dokumen', 'buku_tabungan')->first()?->lampiran_dokumen ? asset('storage/' . $anggota->dokumenAnggota->where('nama_dokumen', 'buku_tabungan')->first()->lampiran_dokumen) : null;

                return $anggota;
            });

            return response()->json(['anggota' => $anggota->values()]);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            return response()->json(['errors'=> $exception->getMessage()]);
        }
    }
    public function searchPemasoks(Request $request)
    {
        $query = $request->input('q');

        $pemasok = DB::table('pemasok')
            ->where('nama_pemasok', 'ILIKE', "%{$query}%")
            ->limit(5)
            ->get();

        return response()->json(['pemasok' => $pemasok]);
    }

    public function showRepayment(string $id)
    {
        $pembiayaan = Pembiayaan::with([
            'anggota.user',
            'anggota.bankAccounts',
            'angsuran.payment',
            'objekPembiayaan.jenisBarang',
            'objekPembiayaan.pemasok',
            'jaminan'
        ])->where('status', '!=', FinancingReqStatusEnum::PAID->value)->findOrFail($id);

        $data = $this->pembayaranAngsuranService->calculateDetails($pembiayaan);

        $data['pengurus'] = auth()->user()->nama;

        $unpaidInstallment = $pembiayaan->angsuran
            ->where('status', 
                InstallmentPaymentScheduleStatusEnum::SCHEDULED->value
            )
            ->sortBy('angsuran_ke')
            ->first();

        $data['angsuran_id'] = $unpaidInstallment?->id;

        return inertia('Admin/Financing/Repayment/Create', [
            'data' => $data,
        ]);
    }

    public function batalkanPermohonan(string $id)
    {
        try {
            $pembiayaan = Pembiayaan::findOrFail($id);

            $allowedStatuses = [
                FinancingReqStatusEnum::PENDING_REVIEW->value,
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::REJECTED->value,
                FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
            ];

            if (!in_array($pembiayaan->status, $allowedStatuses)) {
                return back()->withErrors(['error' => 'Status pembiayaan tidak mengizinkan pembatalan.']);
            }

            $pembiayaan->delete();

            return redirect()->route('admin.pembiayaan.index')->with('success', 'Permohonan pembiayaan berhasil dibatalkan dan dihapus.');
        } catch (Exception $e) {
            Log::error('Error membatalkan permohonan pembiayaan: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membatalkan permohonan.']);
        }
    }

    public function storeRepayment(CreateRepaymentRequest $request)
    {
        try {
            $transaction = $this->pembayaranAngsuranService->processRepayment($request->validated(), auth()->id());

            return redirect()->route('admin.pembiayaan.pembayaran.success')->with('receipt_data', $transaction);

        } catch (Exception $e) {
            Log::error('Error processing repayment: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Gagal memproses pembayaran: ' . $e->getMessage()]);
        }
    }

    public function storeRekening(StoreRekeningRequest $request)
    {
        $validated = $request->validated();

        try {
            $rekening = $this->pembiayaanService->storeRekeningAnggota($validated);

            return response()->json($rekening, 201);
        } catch (Exception $e) {
            Log::error('Error creating rekening: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan rekening. Terjadi kesalahan pada server.'], 500);
        }
    }

    public function showPaymentSuccess(Request $request)
    {
        $data = session('receipt_data');

        if (!$data) {
            return redirect()->route('admin.pembiayaan.index');
        }

        session()->reflash();

        return inertia('Admin/Financing/Repayment/Result', [
            'data' => $data,
        ]);
    }

    public function storeRekeningPayment(StoreRekeningRequest $request)
    {
        $validated = $request->validated();

        try {
            $rekening = $this->pembayaranAngsuranService->storeRekeningAnggota($validated);
            return response()->json($rekening, 201);
        } catch (\Throwable $e) {
            Log::error('Error creating rekening: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan rekening. Terjadi kesalahan pada server.'], 500);
        }
    }

    public function createPayment(Pembiayaan $pembiayaan)
    {
        return Inertia::render('Admin/Financing/Payment/Create', [
            'pembiayaan' => $this->pembayaranAngsuranService->getCreatePaymentData($pembiayaan),
        ]);
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'angsuran_id' => 'required|exists:angsuran,id',
            'pembiayaan_id' => 'required|exists:pembiayaan,id',
            'metode_pembayaran' => 'required|in:Tunai,Non-Tunai',
            'jumlah_angsuran_dibayar' => 'required|numeric|min:1',
            'tgl_pembayaran' => 'required|date',
            'no_rekening' => 'nullable|string|exists:rekening_anggota,no_rekening',
            'bukti_pembayaran' => [
                Rule::requiredIf($request->metode_pembayaran === 'Non-Tunai'),
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ],
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah untuk metode Non-Tunai.',
            'bukti_pembayaran.max' => 'Ukuran bukti pembayaran maksimal 2MB.',
            'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berformat JPG, PNG, atau PDF.',
        ]);

        DB::beginTransaction();
        try {
            $paymentData = $this->pembayaranAngsuranService->processPayment($validated);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->withErrors(['message' => $th->getMessage()]);
        }

        $fileName = $this->pembayaranAngsuranService->generateAndStoreReceipt($paymentData);

        return redirect("/admin/pembiayaan/show/{$paymentData['pembiayaan']->id}")
            ->with([
                'success' => 'Pembayaran berhasil diproses',
                'pdf_url' => $fileName ? asset('storage/' . $fileName) : null,
            ]);
    }

    public function verifyPayment(Request $request, string $paymentId)
    {
        if (!auth()->user()->hasRole('Bendahara') && !auth()->user()->hasRole('Admin')) {
            abort(403, 'Anda tidak memiliki izin untuk memverifikasi pembayaran ini.');
        }

        try {
            $this->pembayaranAngsuranService->verifyPayment($paymentId, auth()->id());

            return back()->with('success', 'Pembayaran berhasil diverifikasi dan jurnal telah dibuat.');
        } catch (\Throwable $th) {
            return back()->withErrors(['error' => 'Gagal memverifikasi pembayaran: ' . $th->getMessage()]);
        }
    }

    public function reschedulePayment(Request $request, Pembiayaan $pembiayaan)
    {
        $tanggalAkhirPeriode = \App\Models\PengaturanUmum::where('key', 'tanggal_akhir_periode')->value('value');

        $validated = $request->validate([
            'angsuran_id'     => 'required|exists:angsuran,id',
            'tgl_jatuh_tempo' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $tanggalAkhirPeriode],
        ], [
            'tgl_jatuh_tempo.before_or_equal' => 'Jadwal angsuran tidak boleh melebihi tanggal akhir periode (' . $tanggalAkhirPeriode . ').'
        ]);

        $installment = \App\Models\Angsuran::where('pembiayaan_id', $pembiayaan->id)
            ->findOrFail($validated['angsuran_id']);

        $originalDate = \Carbon\Carbon::parse($installment->tgl_jatuh_tempo);
        $newDate      = \Carbon\Carbon::parse($validated['tgl_jatuh_tempo']);

        if (!$newDate->isSameMonth($originalDate) || !$newDate->isSameYear($originalDate)) {
            return back()->withErrors([
                'tgl_jatuh_tempo' => 'Reschedule hanya diperbolehkan pada bulan yang sama dengan jatuh tempo saat ini (' . $originalDate->translatedFormat('F Y') . ').'
            ]);
        }

        try {
            $this->pembayaranAngsuranService->rescheduleInstallments(
                $pembiayaan,
                $installment->id,
                $validated['tgl_jatuh_tempo']
            );

            return redirect("/admin/pembiayaan/show/{$pembiayaan->id}")
                ->with('success', 'Jadwal pembayaran berhasil diperbarui');

        } catch (\Throwable $th) {
            return back()->withErrors(['message' => $th->getMessage()]);
        }
    }

    public function storeJenisBarang(Request $request)
    {
        $validatedData = $request->validate([
            'nama_jenis_barang' => 'required|string|max:255|unique:jenis_barang,nama_jenis_barang',
        ]);

        $jenisBarang = JenisBarang::create($validatedData);

        return response()->json($jenisBarang);
    }

    public function storePemasok(Request $request)
    {
        $validatedData = $request->validate([
            'nama_pemasok' => 'required|string|max:255|unique:pemasok,nama_pemasok',
            'alamat_pemasok' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
        ]);

        $pemasok = Pemasok::create($validatedData);

        return response()->json($pemasok);
    }
}
