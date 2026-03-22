<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanPaymentScheduleStatus;
use App\Enums\TransactionStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Mail\ApprovalNotificationMail;
use App\Mail\RejectionNotificationMail;
use App\Models\Financing;
use App\Models\SavingAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class UserController extends Controller
{
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

        $query = User::with([
            'savingAccounts.transactions' => fn($q) =>
                $q->where('status', TransactionStatus::COMPLETED)
        ])
            ->whereHas(
                'role',
                fn($q) =>
                $q->where('name', 'Anggota')
            )
            ->whereNotNull('joined_date')
            ->whereNotNull('member_number');

        $memberBaseQuery = User::whereHas(
            'role',
            fn($q) =>
            $q->where('name', 'Anggota')
        );

        $verifiedMembersQuery = (clone $memberBaseQuery)
            ->whereNotNull('joined_date');

        $totalVerifiedMembers = $verifiedMembersQuery->count();

        $activeMembers = (clone $verifiedMembersQuery)
            ->where('status', UserStatus::ACTIVE)
            ->count();

        $newThisMonth = (clone $verifiedMembersQuery)
            ->whereMonth('joined_date', now()->month)
            ->whereYear('joined_date', now()->year)
            ->count();

        $inReview = (clone $memberBaseQuery)
            ->where('status', UserStatus::INREVIEW)
            ->whereNull('joined_date')
            ->count();

        if ($search) {
            $query->where(
                fn($q) =>
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('member_number', 'ILIKE', "%{$search}%")
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
                'no_anggota' => $user->member_number,
                'name' => $user->name,
                'joined_at' => $user->joined_date
                    ? \Carbon\Carbon::parse($user->joined_date)->format('d/m/Y')
                    : null,
                'phone' => $user->phone_number,
                'status' => $user->status,
                'total_simpanan' => 'Rp ' . number_format(
                    $user->savingAccounts
                        ->flatMap(fn($account) => $account->transactions)
                        ->sum(
                            fn($trx) =>
                            $trx->type === 'Penarikan'
                            ? -$trx->amount
                            : $trx->amount
                        ),
                    0,
                    ',',
                    '.'
                ),
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
                'in_review' => $inReview,

                'active_percent' => $totalVerifiedMembers > 0
                    ? round(($activeMembers / $totalVerifiedMembers) * 100)
                    : 0,

                'new_percent' => $totalVerifiedMembers > 0
                    ? round(($newThisMonth / $totalVerifiedMembers) * 100)
                    : 0,

                'in_review_percent' => ($memberBaseQuery->count()) > 0
                    ? round(($inReview / $memberBaseQuery->count()) * 100)
                    : 0,
            ],
            'statuses' => array_column(UserStatus::cases(), 'value')
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
            'savingAccounts.transactions'
            => function ($query) {
                $query->latest('created_at')
                    ->where('status', TransactionStatus::COMPLETED)
                    ->take(1);
            },
            'heirs',
            'financings.loan.paymentSchedules',
        ])->findOrFail($id);

        $user->profile_picture = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $ktpDoc = $user->userDocs->firstWhere('name', 'ktp');
        $kkDoc = $user->userDocs->firstWhere('name', 'kk');

        $user->financings->each(function ($financing) {
            $financing->loan_payment_paid_count = $financing->loan->paymentSchedules
                ->where('status', LoanPaymentScheduleStatus::PAID->value)
                ->count();
            $financing->next_payment = $financing->loan->paymentSchedules
                ->where('status', LoanPaymentScheduleStatus::SCHEDULED->value)
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
            'loan.paymentSchedules.payment' => fn($q) => $q->latest('payment_date')
        ])->findOrFail($financingId);

        return response()->json($financing->loan->paymentSchedules);
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
                'member_number' => $user->member_number,
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
            'status' => UserStatus::INACTIVE,
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
            ->where('status', UserStatus::INREVIEW)
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
                'member_number' => $user->member_number,
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
}
