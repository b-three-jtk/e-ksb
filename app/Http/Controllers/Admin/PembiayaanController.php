<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PositionEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePreFinancingRequest;
use App\Http\Requests\CreateRepaymentRequest;
use App\Http\Requests\StoreFinancingDraftRequest;
use App\Http\Requests\StoreFinancingRequest;
use App\Models\Account;
use App\Models\AkunSimpanan;
use App\Models\Anggota;
use App\Models\Pembiayaan;
use App\Models\FinancingVerification;
use App\Models\GlobalSetting;
use App\Models\JenisBarang;
use App\Models\JournalEntry;
use App\Models\Pemasok;
use App\Models\Pengguna;
use App\Services\Admin\JurnalService;
use App\Services\Admin\PembayaranAngsuranService;
use App\Services\Admin\PembiayaanService;
use App\Services\PembiayaanService as SharedPembiayaanService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $query = $this->pembiayaanService->getSemuaPembiayaan($search, $tab, $user);

        $pembiayaan = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($f) {
                return [
                    'id' => $f->id,
                    'kode_pembiayaan' => $f->kode_pembiayaan,
                    'tgl_akad' => Carbon::parse($f->tgl_akad)->format('Y-m-d') ?? '',
                    'user' => $f->anggota->user
                        ? ($f->anggota->user->kode_pengguna . ' - ' . $f->anggota->user->nama)
                        : '-',
                    'user_role' => $f->anggota->user?->getRoleNames()->first() ?? '-',
                    'tenor_left' => $f->installment ? max(0, $f->tenor - ($f->installment->where('status', '!=', InstallmentPaymentScheduleStatusEnum::PAID->value)->count())) : null,
                    'product_name' => $f->financingItem?->name,
                    'status' => $f->status,
                ];
            });

        $summary = [
            ['title' => 'Total Pengajuan Pembiayaan Murabahah','value' => $this->pembiayaanService->getTotalPermohonanPembiayaan()],
            ['title' => 'Total Pembiayaan Berlangsung', 'value' => $this->pembiayaanService->getTotalPembiayaanBerlangsung()],
            ['title' => 'Total Modal Belum Diputar', 'value' => $this->pembiayaanService->getModalBelumDiputar()],
        ];

        return inertia('Admin/Financing/Index', [
            'pembiayaan' => $pembiayaan,
            'summary' => $summary,
            'filters' => compact('search', 'perPage', 'tab', 'sortBy', 'sortDir'),
        ]);
    }

    public function show(string $id)
    {
        $pembiayaan = $this->sharedPembiayaanService->getPembiayaanById($id);

        $this->pembiayaanService->computepembiayaanummary($pembiayaan);
        $this->pembiayaanService->computeNextDueDate($pembiayaan);

        $pembiayaan->setRelation('installment', $pembiayaan->installment->map(function ($item) {
            return [
                'installment_no'              => $item->installment_no,
                'installment_trans_code'      => $item->payment?->installment_trans_code,
                'due_date'                    => $item->due_date,
                'payment_date'               => $item->payment?->payment_date,
                'amount'                     => $item->amount,
                'is_early_repayment'         => $item->payment?->is_early_repayment ?? false,
                'installment_payment_receipt' => $item->payment?->installment_payment_receipt ? asset('storage/' . $item->payment->installment_payment_receipt) : null,
            ];
        }));

        return inertia('Admin/Financing/Show', ['data' => $pembiayaan]);
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
                    'name' => $pembiayaan->financingItem->name,
                    'jenis_barang_id' => $pembiayaan->financingItem->jenis_barang_id,
                    'condition' => $pembiayaan->financingItem->condition,
                    'qty' => $pembiayaan->financingItem->qty,
                    'specification' => $pembiayaan->financingItem->specification,
                    'price_per_unit' => $pembiayaan->financingItem->price_per_unit,
                    'harga_perolehan' => $pembiayaan->harga_perolehan,
                    'margin_keuntungan' => $pembiayaan->margin_keuntungan,
                    'pemasok_id' => $pembiayaan->financingItem->pemasok_id,
                    'uang_muka' => $pembiayaan->uang_muka,
                    'metode_pembayaran' => $pembiayaan->metode_pembayaran,
                    'akad_wakalah_date' => $pembiayaan->wakalah?->tgl_akad,
                    'tgl_akad' => $pembiayaan->tgl_akad,
                    'status' => $pembiayaan->status,
                    'tenor' => $pembiayaan->tenor,
                    'harga_perkiraan' => $pembiayaan->harga_perkiraan,
                    'tangguh_payment_date' => $pembiayaan->tangguh_payment_date,
                ],
                'collateral' => [
                    'collateral_type' => $pembiayaan->collateral?->collateral_type,
                    'owner_name' => $pembiayaan->collateral?->owner_name,
                    'estimated_market_value' => $pembiayaan->collateral?->estimated_market_value,
                    'collateral_location' => $pembiayaan->collateral?->collateral_location,
                ],
                'verification' => $pembiayaan->verification->map(function ($item) {
                    return [
                        'final_verification_status' => $item->final_verification_status,
                        'notes' => $item->notes,
                        'verified_by_name' => $item->verifier?->nama,
                        'verified_at' => $item->verified_at?->format('Y-m-d H:i:s'),
                    ];
                })->sortByDesc('verified_at')->values(),
                'documents' => [
                    'family_card' => $this->getDocumentUrl($pembiayaan->anggota->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($pembiayaan->anggota->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($pembiayaan->anggota->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                    'purchase_receipt' => $this->getDocumentUrl($pembiayaan->financingItem->purchase_receipt),
                    'akad_document' => $this->getDocumentUrl($pembiayaan->dokumen_akad),
                    'akad_wakalah_document' => $this->getDocumentUrl($pembiayaan->wakalah?->dokumen_akad),
                ],
                'pemasok' => $pembiayaan->financingItem->pemasok ? [
                    'nama_pemasok' => $pembiayaan->financingItem->pemasok->nama_pemasok,
                    'alamat_pemasok' => $pembiayaan->financingItem->pemasok->alamat_pemasok,
                    'contact' => $pembiayaan->financingItem->pemasok->contact,
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
                'margin_percentage' => GlobalSetting::where('key', 'murabahah_margin_percentage')->where('effective_date', '<=', now())->latest()->first()?->value,
                'pembiayaan' => [
                    'id' => $pembiayaan->id,
                    'kode_pembiayaan' => $pembiayaan->kode_pembiayaan,
                    'name' => $pembiayaan->financingItem->name,
                    'jenis_barang_id' => $pembiayaan->financingItem->jenis_barang_id,
                    'condition' => $pembiayaan->financingItem->condition,
                    'qty' => $pembiayaan->financingItem->qty,
                    'specification' => $pembiayaan->financingItem->specification,
                    'harga_perolehan' => $pembiayaan->harga_perolehan,
                    'margin_keuntungan' => $pembiayaan->margin_keuntungan,
                    'pemasok_id' => $pembiayaan->financingItem->pemasok_id,
                    'uang_muka' => $pembiayaan->uang_muka,
                    'metode_pembayaran' => $pembiayaan->metode_pembayaran,
                    'tgl_akad' => $pembiayaan->tgl_akad,
                    'status' => $pembiayaan->status,
                    'jenis_barang' => $pembiayaan->financingItem->jenisBarang?->nama_jenis_barang,
                    'tenor' => $pembiayaan->tenor,
                    'harga_perkiraan' => $pembiayaan->harga_perkiraan,
                    'tangguh_payment_date' => $pembiayaan->tangguh_payment_date,
                ],
                'collateral' => [
                    'collateral_type' => $pembiayaan->collateral?->collateral_type,
                    'owner_name' => $pembiayaan->collateral?->owner_name,
                    'estimated_market_value' => $pembiayaan->collateral?->estimated_market_value,
                    'collateral_location' => $pembiayaan->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($pembiayaan->anggota->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($pembiayaan->anggota->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($pembiayaan->anggota->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                ],
                'pemasok' => $pembiayaan->financingItem->pemasok ? [
                    'nama_pemasok' => $pembiayaan->financingItem->pemasok->nama_pemasok,
                    'alamat_pemasok' => $pembiayaan->financingItem->pemasok->alamat_pemasok,
                    'contact' => $pembiayaan->financingItem->pemasok->contact,
                ] : null,
            ],
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required',
            'notes' => 'nullable|string',
        ]);

        try {
            $pembiayaan = $this->pembiayaanService->getPembiayaanBelumDireview($id);

                if ($validated['status'] === FinancingReqStatusEnum::APPROVED->value) {

                    $danaAlokasi = Account::where(
                        'account_name',
                        'Dana Alokasi Pembiayaan Murabahah'
                    )->firstOrFail();

                    $danaAlokasiMasuk = JournalEntry::where(
                        'no_ref_account',
                        $danaAlokasi->no_ref_account
                    )
                    ->where('position', PositionEnum::DEBIT->value)
                    ->sum('nominal');

                    $danaAlokasiKeluar = JournalEntry::where(
                        'no_ref_account',
                        $danaAlokasi->no_ref_account
                    )
                    ->where('position', PositionEnum::CREDIT->value)
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

            FinancingVerification::create([
                'pembiayaan_id' => $pembiayaan->id,
                'verified_by' => auth()->id(),
                'final_verification_status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'verified_at' => now(),
            ]);

            if ($validated['status'] === FinancingReqStatusEnum::APPROVED->value) {

                $pembiayaanDalamProses = Account::where(
                    'account_name',
                    'Pembiayaan Dalam Proses'
                )->firstOrFail();

                $danaAlokasi = Account::where(
                    'account_name',
                    'Dana Alokasi Pembiayaan Murabahah'
                )->firstOrFail();

                app(JurnalService::class)->create(
                    [
                        [
                            'account' => $pembiayaanDalamProses->no_ref_account,
                            'position' => PositionEnum::DEBIT->value,
                            'nominal' => $pembiayaan->harga_perkiraan,
                        ],
                        [
                            'account' => $danaAlokasi->no_ref_account,
                            'position' => PositionEnum::CREDIT->value,
                            'nominal' => $pembiayaan->harga_perkiraan,
                        ],
                    ],
                    now()->toDateString(),
                    auth()->id()
                );

                // Jurnal uang muka saat approval (semua payment method)
                if ($pembiayaan->uang_muka > 0) {
                    $uangMukaMurabahah = Account::where(
                        'account_name',
                        'Uang Muka Murabahah'
                    )->firstOrFail();

                    $kas = Account::where(
                        'account_name',
                        'Kas'
                    )->firstOrFail();

                    // Penerimaan uang muka dari anggota
                    app(JurnalService::class)->create(
                        [
                            [
                                'account' => $kas->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                            [
                                'account' => $uangMukaMurabahah->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                    );

                    // Offset uang muka ke piutang murabahah
                    $piutangMurabahah = Account::where(
                        'account_name',
                        'Piutang Murabahah'
                    )->firstOrFail();

                    app(JurnalService::class)->create(
                        [
                            [
                                'account' => $uangMukaMurabahah->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                            [
                                'account' => $piutangMurabahah->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $pembiayaan->uang_muka,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                    );
                }
            }

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

                $hasActiveFinancing = $user->anggota->pembiayaan?->whereIn('status', [FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, FinancingReqStatusEnum::TANGGUH->value])
                ->isNotEmpty() ?? false;

                if ($hasActiveFinancing) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses']);
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
                $this->pembiayaanService->syncFinancingData($user, $request, auth()->id());
            });

            return redirect()->route('admin.pembiayaan.index')
                ->with('success', 'Permohonan pembiayaan berhasil dikirim');

        } catch (Exception $e) {
            Log::error('Error storing pembiayaan: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    public function finalize(StoreFinancingRequest $request)
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

                $hasEligibleSaving = AkunSimpanan::where('anggota_id', $user->anggota->id)
                    ->where('jenis_simpanan', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $hasActiveFinancing = $user->anggota->pembiayaan?->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
                ->isNotEmpty() ?? false;

                if ($hasActiveFinancing) {
                    throw ValidationException::withMessages(['anggota'=> 'Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses']);
                }

                $this->pembiayaanService->syncMemberData($user, $validated['anggota'], $request);
                $pembiayaan = $this->pembiayaanService->syncFinancingData($user, $request, auth()->id());

                if (isset($validated['pembiayaan']['tenor']) && $validated['pembiayaan']['metode_pembayaran'] === FinancingPaymentMethodEnum::INSTALLMENT->value) {
                    $this->pembiayaanService->generateInstallments($pembiayaan);
                } else if ($validated['pembiayaan']['metode_pembayaran'] === FinancingPaymentMethodEnum::TANGGUH->value) {
                    $this->pembiayaanService->generateTangguhSchedule($pembiayaan, $validated['pembiayaan']['tangguh_payment_date']);
                }

                $pembiayaanDalamProses = Account::where(
                    'account_name',
                    'Pembiayaan Dalam Proses'
                )->firstOrFail();

                $piutangMurabahah = Account::where(
                    'account_name',
                    'Piutang Murabahah'
                )->firstOrFail();

                $pendapatanMargin = Account::where(
                    'account_name',
                    'Pendapatan Margin Murabahah'
                )->firstOrFail();
                $danaAlokasi = Account::where(
                    'account_name',
                    'Dana Alokasi Pembiayaan Murabahah'
                )->firstOrFail();

                $kas = Account::where(
                    'account_name',
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
                                    'account' => $danaAlokasi->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $selisih,
                                ],
                                [
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'account' => $pembiayaanDalamProses->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
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
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'account' => $pembiayaanDalamProses->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } else {
                        throw ValidationException::withMessages([
                            'harga_perolehan' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
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
                                'account' => $danaAlokasi->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $selisih,
                            ],
                            [
                                'account' => $kas->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'account' => $pembiayaanDalamProses->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'account' => $pendapatanMargin->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
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
                                'account' => $kas->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'account' => $pembiayaanDalamProses->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'account' => $pendapatanMargin->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $margin,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                        );
                    } else {
                        throw ValidationException::withMessages([
                            'harga_perolehan' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
                    }
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
                                    'account' => $danaAlokasi->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $selisih,
                                ],
                                [
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'account' => $pembiayaanDalamProses->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
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
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'account' => $pembiayaanDalamProses->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } else {
                        throw ValidationException::withMessages([
                            'harga_perolehan' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
                    }
                }
                return $pembiayaan;
            });
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
                $this->pembiayaanService->syncFinancingData($user, $request, auth()->id());
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
        $query = $request->input('q');

        $anggota = Anggota::query()
            ->with(['user:id,kode_pengguna,nama,email,nik,no_telp', 'memberDocs', 'financials', 'heirs', 'memberJobs', 'pembiayaan:id,status', 'akunSimpanan:id,saldo,created_at'])
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
            ->map(function ($anggota) {
                $hasActiveFinancing = $anggota->pembiayaan?->where(
                    'status',
                        FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                        FinancingReqStatusEnum::TANGGUH->value,
                )->isNotEmpty() ?? false;

                $anggota->is_have_no_obligation = !$hasActiveFinancing;

                $hasEligibleSaving = AkunSimpanan::where('anggota_id', $anggota->id)
                    ->where('jenis_simpanan', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                $anggota->heirs = $anggota->heirs->map(function ($heir) {
                    $heir->relationship = $heir->pivot->relationship;
                    return $heir;
                });

                $anggota->is_have_eligible_saving = $hasEligibleSaving;
                $anggota->family_card = $anggota->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment ? asset('storage/' . $anggota->memberDocs->where('doc_name', 'kartu_keluarga')->first()->doc_attachment) : null;
                $anggota->income_slip = $anggota->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment ? asset('storage/' . $anggota->memberDocs->where('doc_name', 'slip_gaji')->first()->doc_attachment) : null;
                $anggota->bank_book = $anggota->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment ? asset('storage/' . $anggota->memberDocs->where('doc_name', 'buku_tabungan')->first()->doc_attachment) : null;

                return $anggota;
            });

        return response()->json(['anggota' => $anggota->values()]);
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
            'installment.payment',
            'financingItem.jenisBarang',
            'financingItem.pemasok',
            'collateral'
        ])->where('status', '!=', FinancingReqStatusEnum::PAID->value)->findOrFail($id);

        $data = $this->pembayaranAngsuranService->calculateDetails($pembiayaan);

        $data['pengurus'] = auth()->user()->nama;

        $unpaidInstallment = $pembiayaan->installment
            ->whereNotIn('status', [
                InstallmentPaymentScheduleStatusEnum::PAID->value,
                InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
            ])
            ->sortBy('installment_no')
            ->first();

        $data['installment_id'] = $unpaidInstallment?->id;

        return inertia('Admin/Financing/Repayment/Create', [
            'data' => $data,
        ]);
    }

    public function storeRepayment(CreateRepaymentRequest $request)
    {
        try {
            $transaction = $this->pembayaranAngsuranService->processRepayment($request->validated(), auth()->id());

            return inertia('Admin/Financing/Repayment/Result', [
                'data' => $transaction,
            ]);

        } catch (Exception $e) {
            Log::error('Error processing repayment: ' . $e->getMessage());
            return inertia('Admin/Financing/Repayment/Result', [
                'error' => 'Gagal memproses pembayaran: ' . $e->getMessage(),
            ]);
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
            'installment_id' => 'required|exists:installments,id',
            'pembiayaan_id'   => 'required|exists:pembiayaan,id',
            'metode_pembayaran' => 'required|string',
            'nominal'        => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
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

    public function reschedulePayment(Request $request, Pembiayaan $pembiayaan)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'due_date'       => ['required', 'date', 'after_or_equal:today'],
        ]);

        try {
            $this->pembayaranAngsuranService->rescheduleInstallments(
                $pembiayaan,
                $validated['installment_id'],
                $validated['due_date']
            );

            return redirect("/admin/pembiayaan/show/{pembiayaan->id}")
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
