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
use App\Models\Financing;
use App\Models\FinancingVerification;
use App\Models\GlobalSetting;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\ProductType;
use App\Models\SavingAccount;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\JurnalService;
use App\Services\Admin\PelunasanService;
use App\Services\Admin\PembiayaanService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PembiayaanController extends Controller
{
    public function __construct(private PembiayaanService $financingService){}
    private function baseQuery(Request $request)
    {
        $verifier = auth()->user();
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');

        return $this->financingService->getSemuaPembiayaan($search, $tab, $verifier);
    }

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

        $query = $this->baseQuery($request)->orderBy($sortBy, $sortDir);

        $financings = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($f) {
                return [
                    'id' => $f->id,
                    'financing_transaction_code' => $f->financing_transaction_code,
                    'akad_date' => $f->akad_date?->format('Y-m-d') ?? '',
                    'user' => $f->member->user
                        ? ($f->member->user->user_code . ' - ' . $f->member->user->name)
                        : '-',
                    'user_role' => $f->member->user?->getRoleNames()->first() ?? '-',
                    'tenor_left' => $f->installment ? max(0, $f->tenor - ($f->installment->where('status', '!=', InstallmentPaymentScheduleStatusEnum::PAID->value)->count())) : null,
                    'product_name' => $f->financingItem?->name,
                    'status' => $f->status,
                ];
            });

        $summary = [
            ['title' => 'Total Pengajuan Pembiayaan Murabahah','value' => $this->financingService->getTotalPermohonanPembiayaan()],
            ['title' => 'Total Pembiayaan Berlangsung', 'value' => $this->financingService->getTotalPembiayaanBerlangsung()],
            ['title' => 'Total Modal Belum Diputar', 'value' => $this->financingService->getModalBelumDiputar()],
        ];

        return inertia('Admin/Financing/Index', [
            'financings' => $financings,
            'summary' => $summary,
            'filters' => compact('search', 'perPage', 'tab', 'sortBy', 'sortDir'),
        ]);
    }

    /**
     * Format member data untuk response
     */
    private function formatMemberData(Member $member): array
    {
        return [
            'id' => $member->id,
            'user_code' => $member->user->user_code,
            'name' => $member->user->name,
            'email' => $member->user->email,
            'nik' => $member->user->nik,
            'phone_number' => $member->user->phone_number,
            'gender' => $member->gender,
            'birth_place' => $member->birth_place,
            'birth_date' => $member->birth_date,
            'marital_status' => $member->marital_status,
            'last_education' => $member->last_education,
            'dependents' => $member->dependents,
            'domicile_address' => $member->domicile_address,
            'residential_address' => $member->residential_address,
            'employment_status' => $member->memberJobs?->employment_status,
            'job_title' => $member->memberJobs?->job_title,
            'company_or_business_name' => $member->memberJobs?->company_or_business_name,
            'business_field' => $member->memberJobs?->business_field,
            'tenure_year' => $member->memberJobs?->tenure_year,
            'workplace_address' => $member->memberJobs?->workplace_address,
            'workplace_contact' => $member->memberJobs?->workplace_contact,
            'gaji_pokok_amount' => $member->financials?->gaji_pokok_amount ?? 0,
            'penghasilan_usaha_amount' => $member->financials?->penghasilan_usaha_amount ?? 0,
            'penghasilan_pasangan_amount' => $member->financials?->penghasilan_pasangan_amount ?? 0,
            'penghasilan_lainnya_amount' => $member->financials?->penghasilan_lainnya_amount ?? 0,
            'biaya_hidup_keluarga_amount' => $member->financials?->biaya_hidup_keluarga_amount ?? 0,
            'biaya_pendidikan_amount' => $member->financials?->biaya_pendidikan_amount ?? 0,
            'jumlah_cicilan_amount' => $member->financials?->jumlah_cicilan_amount ?? 0,
            'jumlah_biaya_lainnya_amount' => $member->financials?->jumlah_biaya_lainnya_amount ?? 0,
            'heirs' => $member->heirs->map(fn($h) => [
                'heir_nik' => $h->heir_nik,
                'heir_name' => $h->heir_name,
                'relationship' => $h->relationship,
                'heir_contact' => $h->heir_contact,
            ])->values(),
        ];
    }

    public function show(string $id)
    {
        $financing = $this->financingService->getPembiataanById($id);

        $this->financingService->computeFinancingSummary($financing);
        $this->financingService->computeNextDueDate($financing);

        $financing->setRelation('installment', $financing->installment->map(function ($item) {
            return [
                'installment_no'              => $item->installment_no,
                'installment_trans_code'      => $item->payment?->installment_trans_code,
                'due_date'                    => $item->due_date,
                'payment_date'               => $item->payment?->payment_date,
                'amount'                     => $item->payment?->nominal,
                'is_early_repayment'         => $item->payment?->is_early_repayment ?? false,
                'installment_payment_receipt' => $item->payment?->installment_payment_receipt ? asset('storage/' . $item->payment->installment_payment_receipt) : null,
            ];
        }));

        return inertia('Admin/Financing/Show', ['data' => $financing]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Financing/Create', [
            'data' => $this->financingService->getDataOpsi(),
        ]);
    }

    public function loadDraft(string $id)
    {
        $financing = $this->financingService->getDraftPembiayaan($id);

        if (!$financing) {
            throw ValidationException::withMessages(['Data pembiayaan tidak ditemukan atau tidak dalam status yang valid untuk dimuat sebagai draft']);
        }

        return inertia('Admin/Financing/Create', [
            'data' => $this->financingService->getDataOpsi(),
            'financing' => [
                'member' => $this->formatMemberData($financing->member),
                'financing' => [
                    'name' => $financing->financingItem->name,
                    'product_type_id' => $financing->financingItem->product_type_id,
                    'condition' => $financing->financingItem->condition,
                    'qty' => $financing->financingItem->qty,
                    'specification' => $financing->financingItem->specification,
                    'price_per_unit' => $financing->financingItem->price_per_unit,
                    'cost_price' => $financing->cost_price,
                    'margin_amount' => $financing->margin_amount,
                    'supplier_id' => $financing->financingItem->supplier_id,
                    'down_payment' => $financing->down_payment,
                    'payment_method' => $financing->payment_method,
                    'akad_wakalah_date' => $financing->wakalah?->akad_date,
                    'akad_date' => $financing->akad_date,
                    'status' => $financing->status,
                    'tenor' => $financing->tenor,
                    'predicted_cost_price' => $financing->predicted_cost_price,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'verification' => $financing->verification->map(function ($item) {
                    return [
                        'final_verification_status' => $item->final_verification_status,
                        'notes' => $item->notes,
                        'verified_by_name' => $item->verifier?->name,
                        'verified_at' => $item->verified_at?->format('Y-m-d H:i:s'),
                    ];
                })->sortByDesc('verified_at')->values(),
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                    'purchase_receipt' => $this->getDocumentUrl($financing->financingItem->purchase_receipt),
                    'akad_document' => $this->getDocumentUrl($financing->signed_akad_document),
                    'akad_wakalah_document' => $this->getDocumentUrl($financing->wakalah?->signed_akad_document),
                ],
                'supplier' => $financing->financingItem->supplier ? [
                    'supplier_name' => $financing->financingItem->supplier->supplier_name,
                    'address' => $financing->financingItem->supplier->address,
                    'contact' => $financing->financingItem->supplier->contact,
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
        $financing = $this->financingService->getPembiayaanBelumDireview($id);

        return inertia('Admin/Financing/Validation', [
            'data' => [
                'member' => $this->formatMemberData($financing->member),
                'margin_percentage' => GlobalSetting::where('key', 'murabahah_margin_percentage')->where('effective_date', '<=', now())->latest()->first()?->value,
                'financing' => [
                    'id' => $financing->id,
                    'financing_transaction_code' => $financing->financing_transaction_code,
                    'name' => $financing->financingItem->name,
                    'product_type_id' => $financing->financingItem->product_type_id,
                    'condition' => $financing->financingItem->condition,
                    'qty' => $financing->financingItem->qty,
                    'specification' => $financing->financingItem->specification,
                    'cost_price' => $financing->cost_price,
                    'margin_amount' => $financing->margin_amount,
                    'supplier_id' => $financing->financingItem->supplier_id,
                    'down_payment' => $financing->down_payment,
                    'payment_method' => $financing->payment_method,
                    'akad_date' => $financing->akad_date,
                    'status' => $financing->status,
                    'product_type' => $financing->financingItem->productType?->product_type_name,
                    'tenor' => $financing->tenor,
                    'predicted_cost_price' => $financing->predicted_cost_price,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                ],
                'supplier' => $financing->financingItem->supplier ? [
                    'supplier_name' => $financing->financingItem->supplier->supplier_name,
                    'address' => $financing->financingItem->supplier->address,
                    'contact' => $financing->financingItem->supplier->contact,
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
            $financing = $this->financingService->getPembiayaanBelumDireview($id);

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

                    if ($saldoDanaAlokasi < $financing->predicted_cost_price) {
                        throw ValidationException::withMessages([
                            'status' =>
                                'Dana alokasi pembiayaan tidak mencukupi. Silakan lakukan alokasi dana terlebih dahulu.'
                        ]);
                    }
                }

            $financing->update([
                'status' => $validated['status'],
            ]);

            FinancingVerification::create([
                'financing_id' => $financing->id,
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
                            'nominal' => $financing->predicted_cost_price,
                        ],
                        [
                            'account' => $danaAlokasi->no_ref_account,
                            'position' => PositionEnum::CREDIT->value,
                            'nominal' => $financing->predicted_cost_price,
                        ],
                    ],
                    now()->toDateString(),
                    auth()->id()
                );
            }

            return redirect()->route('admin.financings.index')->with('success', 'Keputusan validasi berhasil disimpan');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error validating financing: ' . $e->getMessage());

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
                $user = User::with('member.savingAccounts')
                    ->where('user_code', $validated['member']['user_code'])
                    ->firstOrFail();

                if ($user->status !== UserStatusEnum::ACTIVE->value) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus dalam status aktif']);
                }

                $hasEligibleSaving = SavingAccount::where('member_id', $user->member->id)
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $hasActiveFinancing = $user->member->financings?->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
                ->isNotEmpty() ?? false;

                if ($hasActiveFinancing) {
                    throw ValidationException::withMessages(['member' => 'Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses']);
                }

                $validated['financing']['status'] = 'Belum Ditinjau';

                $this->financingService->syncMemberData($user, $validated['member'], $request);
                $this->financingService->syncFinancingData($user, $validated, $request, auth()->id());
            });

            return redirect()->route('admin.financings.index')
                ->with('success', 'Permohonan pembiayaan berhasil dikirim');

        } catch (Exception $e) {
            Log::error('Error storing financing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    public function finalize(StoreFinancingRequest $request)
    {
        try {
            $financing = DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = User::with('member.savingAccounts')
                    ->where('user_code', $validated['member']['user_code'])
                    ->firstOrFail();

                if ($user->status !== UserStatusEnum::ACTIVE->value) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus dalam status aktif']);
                }

                $hasEligibleSaving = SavingAccount::where('member_id', $user->member->id)
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $hasActiveFinancing = $user->member->financings?->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
                ->isNotEmpty() ?? false;

                if ($hasActiveFinancing) {
                    throw ValidationException::withMessages(['member' => 'Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses']);
                }

                $this->financingService->syncMemberData($user, $validated['member'], $request);
                $financing = $this->financingService->syncFinancingData($user, $validated, $request, auth()->id());

                if ($request->hasFile('akad_document_file')) {
                    $financing->update([
                        'signed_akad_document' => $request->file('akad_document_file')->store('documents', 'public'),
                    ]);
                }

                if (isset($validated['financing']['tenor']) && $validated['financing']['payment_method'] === FinancingPaymentMethodEnum::INSTALLMENT->value) {
                    $this->financingService->generateInstallments($financing);
                } else if ($validated['financing']['payment_method'] === FinancingPaymentMethodEnum::TANGGUH->value) {
                    $this->financingService->generateTangguhSchedule($financing, $validated['financing']['tangguh_payment_date']);
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

                $costPrice = $financing->cost_price;
                $margin = $financing->margin_amount;
                $downPayment = $financing->down_payment ??0;

                // Kalo pembayaran pembiayaannya cicilan
                if ($financing->payment_method === FinancingPaymentMethodEnum::INSTALLMENT->value)
                {
                    $uangMukaMurabahah = Account::where(
                        'account_name',
                        'Uang Muka Murabahah'
                    )->firstOrFail();
                    $allocatedAmount = $financing->predicted_cost_price ?? 0;
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
                                    'nominal' => $financing->predicted_cost_price,
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
                            'cost_price' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
                    }

                    if ($downPayment > 0)
                    {
                        app(JurnalService::class)->create(
                            [
                                [
                                    'account' => $kas->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $downPayment,
                                ],
                                [
                                    'account' => $uangMukaMurabahah->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $downPayment,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );

                        app(JurnalService::class)->create(
                            [
                                [
                                    'account' => $uangMukaMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $downPayment,
                                ],
                                [
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $downPayment,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    }
                }

                // Klo pembayaran pembiayaannya cash
                if (
                        $financing->payment_method === FinancingPaymentMethodEnum::CASH->value
                        && $downPayment > 0
                    ) {
                        throw ValidationException::withMessages([
                            'down_payment' => 'Pembayaran cash tidak boleh menggunakan uang muka.'
                        ]);
                    }

                if ($financing->payment_method === FinancingPaymentMethodEnum::CASH->value)
                {
                    $allocatedAmount = $financing->predicted_cost_price ?? 0;
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
                            'cost_price' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
                    }
                }
                return $financing;
            });
            return redirect()->route('admin.financings.index')
                ->with('success', 'Pembiayaan berhasil difinalisasi');
        } catch (Exception $e) {
            Log::error('Error storing financing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    public function saveDraft(StoreFinancingDraftRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = User::with('member.savingAccounts')
                    ->where('user_code', $validated['member']['user_code'])
                    ->firstOrFail();

                $this->financingService->syncMemberData($user, $validated['member'], $request);
                $this->financingService->syncFinancingData($user, $validated, $request, auth()->id());
            });

            return redirect()->route('admin.financings.index')
                ->with('success', 'Draft berhasil disimpan');

        } catch (Exception $e) {
            Log::error('Error saving draft: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan draft: ' . $e->getMessage()]);
        }
    }

    public function searchMembers(Request $request)
    {
        $query = $request->input('q');

        $members = Member::query()
            ->with(['user:id,user_code,name,email,nik,phone_number', 'memberDocs', 'financials', 'heirs', 'memberJobs', 'financings:id,status', 'savingAccounts:id,balance,created_at'])
            ->whereHas('user', function ($q) use ($query) {
                $q->whereHas('roles', fn($roleQ) => $roleQ->where('name', 'Anggota'))
                    ->where('status', UserStatusEnum::ACTIVE->value)
                    ->where(function ($searchQ) use ($query) {
                        $searchQ->where('name', 'ILIKE', "%{$query}%")
                            ->orWhere('user_code', 'ILIKE', "%{$query}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(function ($member) {
                $hasActiveFinancing = $member->financings?->whereIn(
                    'status',
                    [
                        FinancingReqStatusEnum::PENDING_REVIEW->value,
                        FinancingReqStatusEnum::REJECTED->value,
                        FinancingReqStatusEnum::APPROVED->value,
                        FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                        FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    ]
                )->isNotEmpty() ?? false;

                $member->is_have_no_obligation = !$hasActiveFinancing;

                $hasEligibleSaving = SavingAccount::where('member_id', $member->id)
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                $member->is_have_eligible_saving = $hasEligibleSaving;
                $member->family_card = $member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'kk')->first()->doc_attachment) : null;
                $member->income_slip = $member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'slip_gaji')->first()->doc_attachment) : null;
                $member->bank_book = $member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'buku_tabungan')->first()->doc_attachment) : null;

                return $member;
            });

        return response()->json(['members' => $members->values()]);
    }
    public function searchSuppliers(Request $request)
    {
        $query = $request->input('q');

        $suppliers = DB::table('suppliers')
            ->where('supplier_name', 'ILIKE', "%{$query}%")
            ->limit(5)
            ->get();

        return response()->json(['suppliers' => $suppliers]);
    }

    public function showRepayment(string $id, PelunasanService $repaymentService)
    {
        $financing = Financing::with([
            'member.user',
            'installment.payment',
            'financingItem.productType',
            'financingItem.supplier',
            'collateral'
        ])->where('status', '!=', FinancingReqStatusEnum::PAID->value)->findOrFail($id);

        $data = $repaymentService->calculateDetails($financing);

        $data['pengurus'] = auth()->user()->name;

        $unpaidInstallment = $financing->installment
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

    public function storeRepayment(CreateRepaymentRequest $request, PelunasanService $service)
    {
        try {
            $transaction = $service->processRepayment($request->validated(), auth()->id());

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

    public function createPayment(Financing $financing)
    {
        return Inertia::render('Admin/Financing/Payment/Create', [
            'financing' => app(PembiayaanService::class)->getCreatePaymentData($financing),
        ]);
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'financing_id'   => 'required|exists:financings,id',
            'payment_method' => 'required|string',
            'nominal'        => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $paymentData = app(PembiayaanService::class)->processPayment($validated);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->withErrors(['message' => $th->getMessage()]);
        }

        $fileName = app(PembiayaanService::class)->generateAndStoreReceipt($paymentData);

        return redirect("/admin/financings/show/{$paymentData['financing']->id}")
            ->with([
                'success' => 'Pembayaran berhasil diproses',
                'pdf_url' => $fileName ? asset('storage/' . $fileName) : null,
            ]);
    }

    public function reschedulePayment(Request $request, Financing $financing)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'due_date'       => ['required', 'date', 'after_or_equal:today'],
        ]);

        try {
            app(PembiayaanService::class)->rescheduleInstallments(
                $financing,
                $validated['installment_id'],
                $validated['due_date']
            );

            return redirect("/admin/financings/show/{$financing->id}")
                ->with('success', 'Jadwal pembayaran berhasil diperbarui');

        } catch (\Throwable $th) {
            return back()->withErrors(['message' => $th->getMessage()]);
        }
    }

    public function storeProductType(Request $request)
    {
        $validatedData = $request->validate([
            'product_type_name' => 'required|string|max:255|unique:product_types,product_type_name',
        ]);

        $productType = ProductType::create($validatedData);

        return response()->json($productType);
    }

    public function storeSupplier(Request $request)
    {
        $validatedData = $request->validate([
            'supplier_name' => 'required|string|max:255|unique:suppliers,supplier_name',
            'address' => 'required|string|max:255',
            'contact' => 'required|string|max:255',
        ]);

        $supplier = Supplier::create($validatedData);

        return response()->json($supplier);
    }
}
