<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\FinancialCategoryEnum;
use App\Enums\HeirEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Mail\ApprovalNotificationMail;
use App\Mail\RejectionNotificationMail;
use App\Models\Financing;
use App\Models\SavingAccount;
use App\Models\User;
use App\Services\Admin\RegisterMemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use RuntimeException;

class UserController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/User/Create/Index', [
            'educationOptions' => $this->enumOptions(EducationEnum::cases()),
            'maritalStatusOptions' => $this->enumOptions(MaritalStatusEnum::cases()),
            'heirRelationshipOptions' => $this->enumOptions(HeirEnum::cases()),
        ]);
    }

    public function store(StoreMemberRequest $request, RegisterMemberService $registerMemberService)
    {
        $validated = $request->validated();

        try {
            $memberCredentials = $registerMemberService->register($validated, $request);
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
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status');

        $allowedSorts = ['name', 'joined_date'];
        $sortBy = in_array($request->sort_by, $allowedSorts)
            ? $request->sort_by
            : 'joined_date';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $query = User::with('savingAccounts.transactions')
            ->whereHas(
                'role',
                fn($q) =>
                $q->where('role_name', UserRoleEnum::ANGGOTA->value)
            )
            ->whereNotNull('joined_date')
            ->whereNotNull('member_code');

        $memberBaseQuery = User::whereHas(
            'role',
            fn($q) =>
            $q->where('role_name', UserRoleEnum::ANGGOTA->value)
        );

        $verifiedMembersQuery = (clone $memberBaseQuery)
            ->whereNotNull('joined_date');

        $totalVerifiedMembers = $verifiedMembersQuery->count();

        $activeMembers = (clone $verifiedMembersQuery)
            ->where('status', UserStatusEnum::ACTIVE)
            ->count();

        $newThisMonth = (clone $verifiedMembersQuery)
            ->whereMonth('joined_date', now()->month)
            ->whereYear('joined_date', now()->year)
            ->count();

        if ($search) {
            $query->where(
                fn($q) =>
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('member_code', 'ILIKE', "%{$search}%")
                    ->orWhere('phone_number', 'ILIKE', "%{$search}%")
            );
        }

        if ($status) {
            $query->where('status', $status);
        }

        $members = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'no_anggota' => $user->member_code,
                'name' => $user->name,
                'joined_at' => $user->joined_date
                    ? \Carbon\Carbon::parse($user->joined_date)->format('d/m/Y')
                    : null,
                'phone' => $user->phone_number,
                'status' => $user->status,
                'total_simpanan' => 'Rp ' . number_format(
                    DB::table('get_saving_account_balance')->where('user_id', $user->id)->sum('total_balance') ?? 0, 0, ',', '.'),
                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);

        return Inertia::render('Admin/User/List', [
            'members' => $members,
            'filters' => $request->only([
                'search',
                'status',
                'per_page',
                'sort_by',
                'sort_dir'
            ]),
            'summary' => [
                'total_verified' => $totalVerifiedMembers,

                'active' => $activeMembers,
                'new_this_month' => $newThisMonth,

                'active_percent' => $totalVerifiedMembers > 0
                    ? round(($activeMembers / $totalVerifiedMembers) * 100)
                    : 0,

                'new_percent' => $totalVerifiedMembers > 0
                    ? round(($newThisMonth / $totalVerifiedMembers) * 100)
                    : 0,
            ],
            'statuses' => array_column(UserStatusEnum::cases(), 'value')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with([
            'userDocs',
            'role',
            'savingAccounts.transactions',
            'heirs',
            'financings.installment.paymentSchedules',
        ])->findOrFail($id);

        $user->profile_picture = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $ktpDoc = $user->userDocs->firstWhere('name', 'ktp');
        $kkDoc = $user->userDocs->firstWhere('name', 'kk');

        $user->financings->each(function ($financing) {
            $financing->installment_payment_paid_count = $financing->installment->paymentSchedules
                ->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)
                ->count();
            $financing->next_payment = $financing->installment->paymentSchedules
                ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
                ->sortBy('due_date')
                ->first();
        });

        return inertia('Admin/User/Show', [
            'user' => $user,
            'ktp_photo' => $ktpDoc?->attachment ? asset('storage/' . $ktpDoc->attachment) : null,
            'kk_photo' => $kkDoc?->attachment ? asset('storage/' . $kkDoc->attachment) : null,
        ]);
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
            'installment.paymentSchedules.payment' => fn($q) => $q->latest('payment_date')
        ])->findOrFail($financingId);

        return response()->json($financing->installment->paymentSchedules);
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
                'member_code' => $user->member_code,
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

    public function prospectiveMembers(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['name', 'created_at'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $members = User::query()
            ->where('status', UserStatusEnum::RESIGNED_REQUESTED->value)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'member_code' => $user->member_code,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
            ]);

        return Inertia::render('Admin/User/Verification/List', [
            'prospectiveMembers' => $members,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'title' => 'Verifikasi Calon Anggota',
        ]);
    }

    public function submitApproval(Request $request, User $user)
    {
        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'note' => 'nullable|string',
        ]);

        $emailSent = true;

        if ($validated['decision'] === 'approved') {
            // Update status user menjadi Aktif
            $user->update(['status' => 'Aktif']);

            try {
                Mail::to($user->email)->send(new ApprovalNotificationMail($user));
            } catch (\Throwable $e) {
                $emailSent = false;
                Log::error('Failed sending approval notification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            $redirect = redirect()->route('admin.users.prospective')
                ->with('success', 'Status berhasil diperbarui menjadi Aktif untuk ' . $user->name . '.');

            if (!$emailSent) {
                $redirect->with('warning', 'Status berhasil diperbarui, tetapi email notifikasi tidak dapat dikirim. Silakan coba lagi nanti.');
            }

            return $redirect;
        } else {
            $user->update(['status' => 'Ditolak dengan alasan']);

            try {
                Mail::to($user->email)->send(new RejectionNotificationMail($user, $validated['note'] ?? ''));
            } catch (\Throwable $e) {
                $emailSent = false;
                Log::error('Failed sending rejection notification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            $redirect = redirect()->route('admin.users.prospective')
                ->with('success', 'Status berhasil diperbarui menjadi Ditolak untuk ' . $user->name . '.');

            if (!$emailSent) {
                $redirect->with('warning', 'Status berhasil diperbarui, tetapi email pemberitahuan tidak dapat dikirim. Silakan coba lagi nanti.');
            }

            return $redirect;
        }
    }

    public function searchMembers(Request $request)
    {
        $query = $request->get('q');

        $members = User::query()
            ->with('financials', 'heirs')
            ->whereHas('role', fn($q) => $q->where('name', 'Anggota'))
            ->whereNotNull('joined_date')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('member_code', 'ILIKE', "%{$query}%");
            })
            ->where('status', UserStatusEnum::ACTIVE->value)
            ->limit(5)
            ->get()
            ->map(function ($member) {
                $financials = $member->financials ?? collect();

                return [
                    'id' => $member->id,
                    'member_code' => $member->member_code,
                    'name' => $member->name,
                    'email' => $member->email,
                    'nik' => $member->nik,
                    'phone_number' => $member->phone_number,
                    'gender' => $member->gender,
                    'marital_status' => $member->marital_status,
                    'last_education' => $member->last_education,
                    'dependents' => $member->dependents,
                    'birth_place' => $member->birth_place,
                    'birth_date' => $member->birth_date,
                    'domicile_address' => $member->domicile_address,
                    'residential_address' => $member->residential_address,

                    'incomes' => $financials->where('category', FinancialCategoryEnum::INCOME->value)->values(),
                    'expenses' => $financials->where('category', FinancialCategoryEnum::EXPENSE->value)->values(),
                    'heirs' => $member->heirs ?? collect(),
                ];
            });

        return response()->json($members);
    }
}
