<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\HeirEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberAllocationRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Financing;
use App\Models\Heir;
use App\Models\SavingAccount;
use App\Models\User;
use App\Services\Admin\AnggotaService;
use App\Services\Admin\PembiayaanService;
use App\Services\User\AlokasiAnggotaService;
use App\Services\User\PendaftaranAnggotaService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use RuntimeException;

class PenggunaController extends Controller
{

    public function __construct(
        protected AnggotaService $anggotaService
    ) {}

    public function create()
    {
        return Inertia::render('Admin/User/Create/Index', [
            'educationOptions' => $this->enumOptions(EducationEnum::cases()),
            'maritalStatusOptions' => $this->enumOptions(MaritalStatusEnum::cases()),
            'heirRelationshipOptions' => $this->enumOptions(HeirEnum::cases()),
        ]);
    }

    public function store(StoreMemberRequest $request, PendaftaranAnggotaService $pendaftaranAnggotaService)
    {
        $validated = $request->validated();

        try {
            $memberCredentials = $pendaftaranAnggotaService->register($validated, $request);
        } catch (RuntimeException $e) {
            return back()->withErrors([
                'member' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.users.index')->with([
            'success' => 'Anggota berhasil ditambahkan.',
            'member_credentials' => $memberCredentials,
        ]);
    }

    public function allocation(Request $request, AlokasiAnggotaService $alokasiAnggotaService)
    {
        return Inertia::render('Admin/User/Allocation/Index', $alokasiAnggotaService->buildPageData($request));
    }

    public function storeAllocation(StoreMemberAllocationRequest $request, AlokasiAnggotaService $alokasiAnggotaService)
    {
        $alokasiAnggotaService->allocate($request->validated());

        return redirect()->route('admin.users.allocation')->with('success', 'Alokasi anggota berhasil disimpan.');
    }

    private function enumOptions(array $cases): array
    {
        return collect($cases)
            ->map(fn($item) => [
                'value' => $item->value,
                'text' => $item->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Admin/User/List', [
            'members' => $this->anggotaService->getListAnggota($request),
            'filters' => $request->only(['search', 'status', 'per_page', 'sort_by', 'sort_dir']),
            'summary' => $this->anggotaService->getSummary(),
            'statuses' => array_column(UserStatusEnum::cases(), 'value'),
            'can' => [
                'tambah_anggota' => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
                'edit_anggota'   => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, PembiayaanService $service)
    {
        $ktpDoc = null;
        $kkDoc = null;

        $user = User::with([
            'member.memberDocs',
            'roles',
            'member.savingAccounts.transactions' => fn($q) => $q->orderBy('transaction_date', 'desc'),
            'member.savingAccounts',
            'member.heirs',
            'member.financings.installment.payment',
            'member.financings.financingItem',
        ])->findOrFail($id);

        $user->profile_picture = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        if ($user->member) {
            $ktpDoc = $user->member->memberDocs->where('doc_name', 'ktp')->first();
            $kkDoc = $user->member->memberDocs->where('doc_name', 'kartu_keluarga')->first();

            if ($user->member->financings) {
                $user->member->financings->each(function ($financing) use ($service) {
                    $service->computeFinancingSummary($financing);
                    $nextInstallment = $financing->installment
                    ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
                    ->sortBy('due_date')
                    ->first();

                $financing->setAttribute('next_due_date', $nextInstallment?->due_date);
                });
            }
        }

        return inertia('Admin/User/Show', [
            'user' => $user,
            'ktp_photo' => $ktpDoc?->doc_attachment ? asset('storage/' . $ktpDoc->doc_attachment) : null,
            'kk_photo' => $kkDoc?->doc_attachment ? asset('storage/' . $kkDoc->doc_attachment) : null,
        ]);
    }

    public function edit(string $id)
    {
        $user = User::with(['member', 'member.memberDocs' => function ($query) {
            $query->whereIn('doc_name', ['ktp', 'kartu_keluarga']);
        }, 'member.heirs'])->findOrFail($id);

        $user->kk = $user->member?->memberDocs?->firstWhere('doc_name', 'kartu_keluarga')?->doc_attachment
            ? asset('storage/' . $user->member->memberDocs->firstWhere('doc_name', 'kartu_keluarga')->doc_attachment)
            : null;

        $user->ktp = $user->member?->memberDocs?->firstWhere('doc_name', 'ktp')?->doc_attachment
            ? asset('storage/' . $user->member->memberDocs->firstWhere('doc_name', 'ktp')->doc_attachment)
            : null;

        return inertia('Admin/User/Edit', [
            'data' => $user,
            'opsiPendidikan' => $this->enumOptions(EducationEnum::cases()),
            'opsiStatusPerkawinan' => $this->enumOptions(MaritalStatusEnum::cases()),
            'opsiHubunganKeluarga' => $this->enumOptions(HeirEnum::cases()),
        ]);
    }

    public function update(UpdateMemberRequest $request, string $id)
    {
        $validated = $request->validated();

        $user = User::with('member.memberDocs', 'member.heirs')->findOrFail($id);

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->update([
                    'name' => $validated['name'] ?? $user->name,
                    'nik' => $validated['nik'] ?? $user->nik,
                    'email' => $validated['email'] ?? $user->email,
                    'phone_number' => $validated['phone_number'] ?? $user->phone_number,
                ]);

                if ($user->member) {
                    $user->member->update([
                        'gender' => $validated['gender'] ?? $user->member->gender,
                        'birth_place' => $validated['birth_place'] ?? $user->member->birth_place,
                        'birth_date' => $validated['birth_date'] ?? $user->member->birth_date,
                        'residential_address' => $validated['residential_address'] ?? $user->member->residential_address,
                        'domicile_address' => $validated['domicile_address'] ?? $user->member->domicile_address,
                        'last_education' => $validated['last_education'] ?? $user->member->last_education,
                        'marital_status' => $validated['marital_status'] ?? $user->member->marital_status,
                        'dependents' => $validated['dependents'] ?? $user->member->dependents,
                    ]);
                }

                if (!empty($validated['heirs'])) {
                    $syncData = [];

                    foreach ($validated['heirs'] as $heirInput) {
                        $heir = Heir::firstOrCreate(
                            ['heir_nik' => $heirInput['heir_nik']],
                            [
                                'heir_name' => $heirInput['heir_name'],
                                'heir_contact' => $heirInput['heir_contact'] ?? null,
                            ]
                        );

                        $syncData[$heir->heir_nik] = ['relationship' => $heirInput['relationship']];
                    }

                    $user->member->heirs()->sync($syncData);
                } else {
                    $user->member->heirs()->detach();
                }

                if (isset($validated['ktp_file'])) {
                    $user->member->memberDocs()->firstOrCreate([
                        'doc_name' => 'ktp',
                        'doc_attachment' => $validated['ktp_file']->store('member_docs', 'public'),
                        'member_id' => $user->member->id,
                    ]);
                }

                if (isset($validated['kk_file'])) {
                    $user->member->memberDocs()->firstOrCreate([
                        'doc_name' => 'kartu_keluarga',
                        'doc_attachment' => $validated['kk_file']->store('member_docs', 'public'),
                        'member_id' => $user->member->id
                    ]);
                }
            });

            return redirect()->route('admin.users.index');
        } catch (Exception $e) {
            Log::info('error'. $e->getMessage());
            return back()->withErrors([
                'member' => $e->getMessage(),
            ]);
        }
    }

    public function getMutasi($accountId)
    {
        $account = SavingAccount::with([
            'transactions' => fn($q) => $q->latest('transaction_date')
        ])->findOrFail($accountId);

        return response()->json($account->transactions);
    }

    public function getRiwayat($financingId)
    {
        $financing = Financing::with([
            'installment' => fn($q) => $q->orderBy('installment_no', 'asc'),
            'installment.payment'
        ])->findOrFail($financingId);

        if ($financing->installment->isEmpty()) {
            return response()->json([]);
        }

        return response()->json($financing->installment);
    }

    public function verificationDetail(User $user)
    {
        $user->load('userDocs');

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $idCard = $user->userDocs
            ->firstWhere('name', 'ktp');
        $idCardUrl = $idCard?->attachment ? asset('storage/' . $idCard->attachment) : null;

        return Inertia::render('Admin/User/Verification/Show', [
            'member' => [
                'id' => $user->id,
                'user_code' => $user->user_code,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'photo_url' => $photoUrl,
                'id_card_url' => $idCardUrl,
            ],
        ]);
    }

    public function updateStatusToInactive(string $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'status' => UserStatusEnum::INACTIVE,
        ]);

        return redirect()->back();
    }
}
