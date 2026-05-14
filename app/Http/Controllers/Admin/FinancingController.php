<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConditionEnum;
use App\Enums\EducationEnum;
use App\Enums\FinancialCategoryEnum;
use App\Enums\FinancialCostEnum;
use App\Enums\FinancialIncomeEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\HeirEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\PositionEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRepaymentRequest;
use App\Http\Requests\StoreFinancingRequest;
use App\Models\Financing;
use App\Models\InstallmentPaymentSchedule;
use App\Models\InstallmentPaymentTransaction;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FinancingController extends Controller
{
    private function baseQuery(Request $request)
    {
        $verifier = auth()->user();
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');

        return Financing::with([
            'member.user' => function ($query) {
                $query->select('id', 'name', 'user_code');
            },
            'installment' => function ($query) {
                $query->withCount([
                    'paymentSchedules' => function ($q) {
                        $q->where('installment_schedule_status', '!=', InstallmentPaymentScheduleStatusEnum::PAID->value);
                    }
                ]);
            },
            'financingItem.productType' => function ($query) {
                $query->select('product_types.id', 'product_types.product_type_name');
            }
        ])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('member.user', function ($userQuery) use ($search) {
                    $userQuery->where(function ($userSearchQuery) use ($search) {
                        $userSearchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('user_code', 'like', "%{$search}%");
                    });
                });
            })
            ->when($tab === 'request', function ($q) use ($verifier) {
                if (in_array($verifier->getRoleNames()->first(), ['Ketua Murabahah'])) {
                    $q->where(
                        'financing_status',
                        FinancingReqStatusEnum::PENDING_REVIEW->value,
                    );
                } else if (in_array($verifier->getRoleNames()->first(), ['Staf Murabahah'])) {
                    $q->whereIn('financing_status', [
                        FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                    ]);
                } else {
                    $q->where('financing_status', FinancingReqStatusEnum::WAITING_DOCUMENTS->value);
                }
            })
            ->when($tab === 'validated', function ($q) {
                $q->whereIn('financing_status', [
                    FinancingReqStatusEnum::APPROVED->value,
                    FinancingReqStatusEnum::REJECTED->value,
                ]);
            })
            ->when($tab === 'active', function ($q) {
                $q->where(
                    'financing_status',
                    FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                );
            })->latest('updated_at');
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
                    'akad_date' => $f->akad_date,
                    'user' => $f->member->user
                        ? ($f->member->user->user_code . ' - ' . $f->member->user->name)
                        : '-',
                    'tenor_left' => $f->installment?->payment_schedules_count ?? 0,
                    'product_name' => $f->financingItem?->name,
                    'financing_status' => $f->financing_status,
                ];
            });

        $summary = [
            [
                'title' => 'Total Pengajuan Pembiayaan Murabahah',
                'value' => Financing::whereIn('financing_status', [
                    FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                    FinancingReqStatusEnum::PENDING_REVIEW->value,
                ])->count()
            ],
            ['title' => 'Total Pembiayaan Berlangsung', 'value' => Financing::where('financing_status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)->count()],
            ['title' => 'Total Modal Belum Diputar', 'value' => $this->getModalBelumDiputar()],
        ];

        return inertia('Admin/Financing/Index', [
            'financings' => $financings,
            'summary' => $summary,
            'filters' => compact('search', 'perPage', 'tab', 'sortBy', 'sortDir'),
        ]);
    }

    private function getModalBelumDiputar()
    {
        $modalCredit = JournalEntry::
            with([
                'account' => function ($q) {
                    $q->where('account_name', 'Modal Murabahah');
                }
            ])
            ->where('position', PositionEnum::CREDIT->value)
            ->sum('nominal');

        $modalDebit = JournalEntry::
            with([
                'account' => function ($q) {
                    $q->where('account_name', 'Modal Murabahah');
                }
            ])
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        return $modalCredit - $modalDebit;
    }

    /**
     * Get common dropdown data
     */
    private function getCommonData(): array
    {
        return [
            'educations' => array_column(EducationEnum::cases(), 'value'),
            'marriageStatuses' => array_column(MaritalStatusEnum::cases(), 'value'),
            'incomes' => array_column(FinancialIncomeEnum::cases(), 'value'),
            'expenses' => array_column(FinancialCostEnum::cases(), 'value'),
            'relationships' => array_column(HeirEnum::cases(), 'value'),
            'conditions' => array_column(ConditionEnum::cases(), 'value'),
            'productTypes' => DB::table('product_types')->select('id', 'product_type_name')->get(),
        ];
    }

    /**
     * Format member data untuk response
     */
    private function formatMemberData(Member $member): array
    {
        $financials = $member->financials ?? collect();
        $job = $member->memberJobs;

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
            'spouse_name' => $member->spouse_name,
            'last_education' => $member->last_education,
            'dependents' => $member->dependents,
            'domicile_address' => $member->domicile_address,
            'residential_address' => $member->residential_address,
            'job_title' => $job?->job_title,
            'company_or_business_name' => $job?->company_or_business_name,
            'business_field' => $job?->business_field,
            'tenure_year' => $job?->tenure_year,
            'workplace_address' => $job?->workplace_address,
            'workplace_contact' => $job?->workplace_contact,
            'incomes' => $financials->where('category', FinancialCategoryEnum::INCOME->value)->map(fn($f) => [
                'financial_type' => $f->financial_type,
                'amount' => $f->amount,
            ])->values(),
            'expenses' => $financials->where('category', FinancialCategoryEnum::EXPENSE->value)->map(fn($f) => [
                'financial_type' => $f->financial_type,
                'amount' => $f->amount,
            ])->values(),
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
        $financing = Financing::with(['financingItem.productType', 'installment.paymentSchedules.payment', 'financingItem.supplier', 'collateral'])->findOrFail($id);
        $financing->total_price = ($financing->financingItem->cost_price ?? 0) + ($financing->financingItem->margin_amount ?? 0) - ($financing->down_payment ?? 0);

        $installment = $financing->installment;
        if ($installment && $installment->paymentSchedules?->count() > 0) {
            $total_margin_paid = 0;
            $total_principal_paid = 0;

            foreach ($installment->paymentSchedules as $schedule) {
                if ($schedule->payment && $schedule->payment->count() > 0) {
                    $total_margin_paid += $schedule->payment->sum('margin_paid') ?? 0;
                    $total_principal_paid += $schedule->payment->sum('principal_paid') ?? 0;
                }
            }

            $financing->total_margin_paid = $total_margin_paid;
            $financing->total_principal_paid = $total_principal_paid;
            $financing->remaining_balance = $financing->total_price - ($total_margin_paid + $total_principal_paid);
            $financing->total_monthly_payment = $financing->financingItem->margin_amount + $financing->financingItem->cost_price - $financing->down_payment / $financing->installment->tenor;

            if ($financing->installment?->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->installment->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
        } else {
            $financing->total_margin_paid = 0;
            $financing->total_principal_paid = 0;
            $financing->remaining_balance = $financing->total_price;
        }

        return inertia('Admin/Financing/Show', [
            'data' => $financing
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Financing/Create', [
            'data' => $this->getCommonData(),
        ]);
    }

    /**
     * Load draft financing
     */
    public function loadDraft(string $id)
    {
        $financing = Financing::where('id', $id)
            ->whereIn('financing_status', [
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::APPROVED->value,
                FinancingReqStatusEnum::REJECTED->value,
            ])
            ->with([
                'member.user',
                'member.financials',
                'member.memberDocs',
                'member.heirs',
                'member.memberJobs',
                'financingItem.productType',
                'financingItem.supplier',
                'collateral',
            ])
            ->first();

        if (!$financing) {
            return inertia('Admin/Financing/Create', [
                'data' => $this->getCommonData(),
                'financing' => null,
            ]);
        }

        Log::info('Loading draft financing with ID: ' . asset($financing->member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment));

        return inertia('Admin/Financing/Create', [
            'data' => $this->getCommonData(),
            'financing' => [
                'member' => $this->formatMemberData($financing->member),
                'financing' => [
                    'name' => $financing->financingItem->name,
                    'product_type_id' => $financing->financingItem->product_type_id,
                    'brand' => $financing->financingItem->brand,
                    'condition' => $financing->financingItem->condition,
                    'qty' => $financing->financingItem->qty,
                    'request_description' => $financing->financingItem->request_description,
                    'cost_price' => $financing->financingItem->cost_price,
                    'margin_amount' => $financing->financingItem->margin_amount,
                    'supplier_id' => $financing->financingItem->supplier_id,
                    'down_payment' => $financing->down_payment,
                    'is_wakalah' => $financing->is_wakalah,
                    'payment_method' => $financing->payment_method,
                    'akad_date' => $financing->akad_date,
                    'notes' => $financing->notes,
                    'financing_status' => $financing->financing_status,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                    'down_payment_proof' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'down_payment_proof')->first()?->doc_attachment),
                    'purchase_receipt' => $this->getDocumentUrl($financing->financingItem->purchase_receipt),
                    'akad_document' => $this->getDocumentUrl($financing->signed_akad_document),
                ],
                'supplier' => $financing->financingItem->supplier ? [
                    'supplier_name' => $financing->financingItem->supplier->supplier_name,
                    'contact' => $financing->financingItem->supplier->contact,
                    'address' => $financing->financingItem->supplier->address,
                    'website_url' => $financing->financingItem->supplier->website_url,
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
        $financing = Financing::where('id', $id)
            ->where('financing_status', FinancingReqStatusEnum::PENDING_REVIEW->value)
            ->with([
                'member.user',
                'member.financials',
                'member.memberDocs',
                'member.heirs',
                'member.memberJobs',
                'financingItem.productType',
                'financingItem.supplier',
                'collateral',
            ])
            ->first();

        if (!$financing) {
            return redirect()->route('admin.financing.index')->withErrors(['error' => 'Data pembiayaan tidak ditemukan atau tidak dalam status yang valid untuk divalidasi']);
        }

        return inertia('Admin/Financing/Validation', [
            'data' => [
                'member' => $this->formatMemberData($financing->member),
                'financing' => [
                    'id' => $financing->id,
                    'financing_transaction_code' => $financing->financing_transaction_code,
                    'name' => $financing->financingItem->name,
                    'product_type_id' => $financing->financingItem->product_type_id,
                    'brand' => $financing->financingItem->brand,
                    'condition' => $financing->financingItem->condition,
                    'qty' => $financing->financingItem->qty,
                    'request_description' => $financing->financingItem->request_description,
                    'cost_price' => $financing->financingItem->cost_price,
                    'margin_amount' => $financing->financingItem->margin_amount,
                    'supplier_id' => $financing->financingItem->supplier_id,
                    'down_payment' => $financing->down_payment,
                    'is_wakalah' => $financing->is_wakalah,
                    'payment_method' => $financing->payment_method,
                    'akad_date' => $financing->akad_date,
                    'notes' => $financing->notes,
                    'financing_status' => $financing->financing_status,
                    'product_type' => $financing->financingItem->productType?->product_type_name,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                    'purchase_receipt' => $this->getDocumentUrl($financing->financingItem->purchase_receipt),
                    'akad_document' => $this->getDocumentUrl($financing->signed_akad_document),
                    'collateral_proof' => $this->getDocumentUrl($financing->collateral?->collateral_proof),
                ],
                'supplier' => $financing->financingItem->supplier ? [
                    'supplier_name' => $financing->financingItem->supplier->supplier_name,
                    'contact' => $financing->financingItem->supplier->contact,
                    'address' => $financing->financingItem->supplier->address,
                    'website_url' => $financing->financingItem->supplier->website_url,
                ] : null,
            ],
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $validated = $request->validate([
            'financing_status' => 'required',
            'notes' => 'nullable|string',
        ]);

        try {
            $financing = Financing::where('id', $id)
                ->where('financing_status', FinancingReqStatusEnum::PENDING_REVIEW->value)
                ->firstOrFail();

            $fi = $financing->update([
                'financing_status' => $validated['financing_status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            Log::info('Financing with ID ' . $id . ' updated with status: ' . $validated['notes']);

            return redirect()->route('admin.financing.index')->with('success', 'Keputusan validasi berhasil disimpan');
        } catch (Exception $e) {
            Log::error('Error validating financing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan keputusan validasi']);
        }
    }

    /**
     * Store financing
     */
    public function store(StoreFinancingRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $user = User::with('member')->where('user_code', $validated['member']['user_code'])->firstOrFail();
            $verifier = auth()->user();

            // Update user
            $user->update([
                'name' => $validated['member']['name'],
                'nik' => $validated['member']['nik'],
                'email' => $validated['member']['email'] ?? $user->email,
                'phone_number' => $validated['member']['phone_number'] ?? $user->phone_number,
            ]);

            // Update member
            $user->member->update([
                'gender' => $validated['member']['gender'] ?? $user->member->gender,
                'birth_place' => $validated['member']['birth_place'] ?? $user->member->birth_place,
                'birth_date' => $validated['member']['birth_date'] ?? $user->member->birth_date,
                'last_education' => $validated['member']['last_education'] ?? $user->member->last_education,
                'domicile_address' => $validated['member']['domicile_address'] ?? $user->member->domicile_address,
                'residential_address' => $validated['member']['residential_address'] ?? $user->member->residential_address,
                'spouse_name' => $validated['member']['spouse_name'] ?? $user->member->spouse_name,
                'marital_status' => $validated['member']['marital_status'] ?? $user->member->marital_status,
                'dependents' => $validated['member']['dependents'] ?? $user->member->dependents,
            ]);

            // Sync heirs
            $user->member->heirs()->delete();
            Log::info('Syncing heirs: ' . !empty($validated['member']['heirs'] ?? []));
            if (!empty($validated['member']['heirs'] ?? [])) {
                $user->member->heirs()->createMany($validated['member']['heirs']);
            }

            // Sync documents
            $documents = [
                'kartu_keluarga' => 'family_card_file',
                'slip_gaji' => 'income_slip_file',
                'buku_tabungan' => 'bank_book_file',
            ];
            foreach ($documents as $docName => $fileField) {
                if ($request->hasFile($fileField)) {
                    $user->member->memberDocs()->updateOrCreate(
                        ['doc_name' => $docName],
                        ['doc_attachment' => $request->file($fileField)->store('documents', 'public')]
                    );
                }
            }

            // Sync financials
            $user->member->financials()->delete();
            if (!empty($validated['member']['incomes'] ?? [])) {
                foreach ($validated['member']['incomes'] as $income) {
                    $user->member->financials()->create([
                        'financial_type' => $income['financial_type'],
                        'amount' => $income['amount'],
                        'category' => FinancialCategoryEnum::INCOME->value,
                    ]);
                }
            }
            if (!empty($validated['member']['expenses'] ?? [])) {
                foreach ($validated['member']['expenses'] as $expense) {
                    $user->member->financials()->create([
                        'financial_type' => $expense['financial_type'],
                        'amount' => $expense['amount'],
                        'category' => FinancialCategoryEnum::EXPENSE->value,
                    ]);
                }
            }

            // Sync job
            $user->member->memberJobs()->delete();
            if (isset($validated['member']['job_title'])) {
                $user->member->memberJobs()->create([
                    'job_title' => $validated['member']['job_title'] ?? null,
                    'company_or_business_name' => $validated['member']['company_or_business_name'] ?? null,
                    'business_field' => $validated['member']['business_field'] ?? null,
                    'tenure_year' => $validated['member']['tenure_year'] ?? null,
                    'workplace_address' => $validated['member']['workplace_address'] ?? null,
                    'workplace_contact' => $validated['member']['workplace_contact'] ?? null,
                ]);
            }

            if (isset($validated['financing']['name'])) {
                $financing = Financing::updateOrCreate(
                    ['member_id' => $user->member->id],
                    [
                        'down_payment' => $validated['financing']['down_payment'] ?? 0,
                        'akad_date' => $validated['financing']['akad_date'] ?? null,
                        'is_wakalah' => $validated['financing']['is_wakalah'] ?? null,
                        'payment_method' => $validated['financing']['payment_method'] ?? null,
                        'updated_by' => $verifier->id,
                        'financing_status' => $validated['financing']['financing_status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                    ]
                );

                if (isset($validated['supplier'])) {
                    $supplier = Supplier::updateOrCreate(
                        ['supplier_name' => $validated['supplier']['supplier_name']],
                        [
                            'contact' => $validated['supplier']['contact'] ?? null,
                            'address' => $validated['supplier']['address'] ?? null,
                            'website_url' => $validated['supplier']['website_url'] ?? null,
                        ]
                    );
                }

                $financing->financingItem()->updateOrCreate(
                    ['financing_id' => $financing->id],
                    [
                        'name' => $validated['financing']['name'] ?? null,
                        'brand' => $validated['financing']['brand'] ?? null,
                        'request_description' => $validated['financing']['request_description'] ?? null,
                        'qty' => $validated['financing']['qty'] ?? null,
                        'condition' => $validated['financing']['condition'] ?? null,
                        'cost_price' => $validated['financing']['cost_price'] ?? null,
                        'margin_amount' => $validated['financing']['margin_amount'] ?? null,
                        'product_type_id' => $validated['financing']['product_type_id'] ?? null,
                        'supplier_id' => $supplier?->id ?? null,
                        'purchase_receipt' => $request->hasFile('purchase_receipt_file')
                            ? $request->file('purchase_receipt_file')->store('documents', 'public')
                            : null,
                    ]
                );

                if (isset($validated['collateral']['collateral_type'])) {
                    $financing->collateral()->updateOrCreate(
                        ['financing_id' => $financing->id],
                        [
                            'collateral_type' => $validated['collateral']['collateral_type'] ?? null,
                            'owner_name' => $validated['collateral']['owner_name'] ?? null,
                            'estimated_market_value' => $validated['collateral']['estimated_market_value'] ?? null,
                            'collateral_location' => $validated['collateral']['collateral_location'] ?? null,
                        ]
                    );
                }

                if ($request->hasFile('akad_document_file')) {
                    $financing->update([
                        'signed_akad_document' => $request->file('akad_document_file')->store('documents', 'public'),
                    ]);
                }

                if (isset($validated['tenor'])) {
                    $installment = $financing->installment()->create([
                        'financing_id' => $financing->id,
                        'tenor' => $validated['tenor'],
                    ]);

                    $installment->generatePaymentSchedules($validated['tenor'], $validated['financing']['akad_date']);
                }
            }

            DB::commit();

            return redirect()->route('admin.financing.index')->with('success', 'Permohonan pembiayaan berhasil disimpan');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing draft: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    /**
     * Search members dengan optimized query
     */
    public function searchMembers(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json(['members' => []]);
        }

        $members = Member::query()
            ->with(['user:id,user_code,name,email,nik,phone_number,role_id', 'memberDocs', 'financials', 'heirs', 'memberJobs'])
            ->whereHas('user', function ($q) use ($query) {
                $q->whereHas('role', fn($roleQ) => $roleQ->where('name', 'Anggota'))
                    ->where('status', UserStatusEnum::ACTIVE->value)
                    ->where(function ($searchQ) use ($query) {
                        $searchQ->where('name', 'ILIKE', "%{$query}%")
                            ->orWhere('user_code', 'ILIKE', "%{$query}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(fn($member) => $this->formatMemberData($member));

        return response()->json(['members' => $members]);
    }

    /**
     * Search suppliers
     */
    public function searchSuppliers(Request $request)
    {
        $query = $request->input('q');

        $suppliers = DB::table('suppliers')
            ->where('supplier_name', 'ILIKE', "%{$query}%")
            ->limit(5)
            ->get();

        Log::info('Search suppliers with query: ' . $query . ', found: ' . $suppliers);

        return response()->json(['suppliers' => $suppliers]);
    }

    public function showRepayment(string $id)
    {
        $data = [];
        $data['financing'] = Financing::
            with('member.user', 'installment.paymentSchedules.payment', 'financingItem.productType', 'financingItem.supplier', 'collateral')
            ->findOrFail($id);

        $data['total_paid_installments'] = $data['financing']->installment->paymentSchedules->where('installment_schedule_status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();
        $data['principal_per_month'] = ($data['financing']->financingItem->cost_price - $data['financing']->down_payment) / $data['financing']->installment->tenor;
        $data['margin_per_month'] = $data['financing']->financingItem->margin_amount / $data['financing']->installment->tenor;
        $data['tsaman_naqdy'] = ($data['financing']->financingItem->cost_price - $data['financing']->down_payment) + ($data['financing']->financingItem->margin_amount / $data['financing']->installment->tenor);
        $data['qimah_ismiyyah'] = ($data['financing']->financingItem->cost_price - $data['financing']->down_payment) + $data['financing']->financingItem->margin_amount;
        $data['margin_berjalan'] = $data['margin_per_month'] * $data['total_paid_installments'];
        $data['installment_per_month'] = ($data['financing']->financingItem->cost_price + $data['financing']->financingItem->margin_amount - $data['financing']->down_payment) / $data['financing']->installment->tenor;
        $data['qimah_haliyyah'] = $data['tsaman_naqdy'] + ($data['margin_per_month'] * ($data['total_paid_installments']));
        $data['total_paid_amount'] = ($data['qimah_ismiyyah'] / $data['financing']->installment->tenor) * $data['total_paid_installments'];
        $data['repayment_total'] = $data['qimah_haliyyah'] - $data['total_paid_amount'];

        $data['principal_paid'] = $data['principal_per_month'] * $data['total_paid_installments'];
        $data['margin_paid'] = $data['margin_per_month'] * $data['total_paid_installments'];
        $data['pengurus'] = auth()->user()->name;

        return inertia('Admin/Financing/Repayment/Create', [
            'data' => $data,
        ]);
    }

    public function storeRepayment(CreateRepaymentRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {

            $unPaidSchedules = InstallmentPaymentSchedule::with('installment.financing.member.user', 'installment.financing.financingItem')
                ->where('installment_id', $data['installment_id'])
                ->where('installment_schedule_status', '!=', InstallmentPaymentScheduleStatusEnum::PAID->value)
                ->orderBy('installment_number', 'asc')
                ->get();

            foreach ($unPaidSchedules as $schedule) {
                $schedule->update([
                    'installment_schedule_status' => InstallmentPaymentScheduleStatusEnum::CANCELLED->value
                ]);
            }

            $firstSchedule = $unPaidSchedules->first();
            $financing = $firstSchedule->installment->financing;
            $member = $financing->member;
            $user = $member->user;

            $transaction = InstallmentPaymentTransaction::create([
                'installment_trans_code' => 'LP' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT), // temporary
                'principal_paid' => $data['principal_paid'],
                'margin_paid' => $data['margin_paid'],
                'installment_payment_method' => $data['method'],
                'is_early_repayment' => true,
                'payment_date' => now(),
                'installment_payment_schedule_id' => $firstSchedule->id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            $strukturData = [
                'no_transaksi' => $transaction->installment_trans_code,
                'tanggal' => $transaction->payment_date,
                'no_anggota' => $user->user_code,
                'nama_anggota' => $user->name,
                'no_telp' => $user->phone_number,
                'financing_code' => $financing->financing_transaction_code,
                'product_name' => $financing->financingItem->name,
                'principal_paid' => $data['principal_paid'],
                'margin_paid' => $data['margin_paid'],
                'metode' => $data['method'],
                'pengurus' => auth()->user()->name,
                'tsaman_naqdy' => $data['tsaman_naqdy'],
                'qimah_ismiyyah' => $data['qimah_ismiyyah'],
                'qimah_haliyyah' => $data['qimah_haliyyah'],
            ];

            return Inertia::render('Admin/Financing/Repayment/Create', [
                'struk' => $strukturData,
                'message' => 'Pelunasan berhasil diproses. Total pembayaran: ' . number_format($data['principal_paid'] + $data['margin_paid'], 0, ',', '.'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing repayment: ' . $e->getMessage());
            return back()->withErrors(['message' => 'Terdapat kesalahan saat memproses pelunasan: ' . $e->getMessage()]);
        }
    }
}
