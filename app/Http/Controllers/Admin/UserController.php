<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WorkUnit;
use App\Mail\ApprovalNotificationMail;
use App\Mail\RejectionNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Enums\UserStatus;
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

        $query = User::with('savingAccounts');

        if ($search) {
            $query->where(fn ($q) =>
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
            ->through(fn ($user) => [
                'id' => $user->id,
                'no_anggota' => $user->member_number,
                'name' => $user->name,
                'joined_at' => $user->joined_date
                    ? \Carbon\Carbon::parse($user->joined_date)->format('d/m/Y')
                    : null,
                'phone' => $user->phone_number,
                'status' => $user->status,
                'total_simpanan' => 'Rp ' . number_format(
                    $user->savingAccounts->sum('balance'),
                    0,
                    ',',
                    '.'
                ),
                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : "https://i.pravatar.cc/40?u=$user->id",
            ]);

        return Inertia::render('Admin/User/ListMember', [
            'members'  => $members,
            'filters'  => $request->only([
                'search', 'status', 'per_page', 'sort_by', 'sort_dir'
            ]),
            'summary'  => [
                'active' => User::where('status', 'Aktif')->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
                'in_review' => User::where('status', 'Dalam Peninjauan')->count(),
            ],
            'statuses' => [
                'Aktif',
                'Tidak Aktif',
                'Mengundurkan Diri',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['role', 'workUnit', 'savingAccounts.transactions' => function($query) {$query->latest('created_at')->take(1);}, 'heirs', 'userDocs', 'financings.loan.payments'])->findOrFail($id);
        return inertia('Admin/User/Show', ['user' => $user]);
    }

    public function verificationDetail(User $user)
    {
        $user->load(['workUnit:id,name', 'userDocs']);

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $idCard = $user->userDocs
            ->first(fn ($doc) => strtolower($doc->name) === 'ktp');
        $idCardUrl = $idCard?->attachment ? asset('storage/' . $idCard->attachment) : null;

        return Inertia::render('Admin/User/Verification/Show', [
            'member' => [
                'id' => $user->id,
                'member_number' => $user->member_number,
                'name' => $user->name,
                'nik' => $user->nik,
                'work_unit' => $user->workUnit->name,
                'institution' => $user->institution,
                'email' => $user->email,
                'photo_url' => $photoUrl,
                'id_card_url' => $idCardUrl,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
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
            ->where('status', 'Dalam Peninjauan')
            ->with('workUnit:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->work_unit_id, function ($query, $unitId) {
                $query->where('work_unit_id', $unitId);
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'member_number' => $user->member_number,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'unit_kerja' => $user->workUnit?->name ?? '-',
            ]);

        return Inertia::render('Admin/User/Verification/ProspectiveMembers', [
            'prospectiveMembers' => $members,
            'filters' => [
                'search' => $request->search,
                'work_unit_id' => $request->work_unit_id,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'workUnits' => WorkUnit::select('id', 'name')->get(),
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
            // Update status user menjadi Ditolak
            $user->update(['status' => 'Ditolak']);

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

    /**
     * Display the user's public profile
     */
    public function profile(User $user)
    {
        $user->load(['role', 'workUnit']);

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;

        return Inertia::render('User/Profile/Show', [
            'user' => [
                'id' => $user->id,
                'member_number' => $user->member_number,
                'name' => $user->name,
                'nik' => $user->nik,
                'birth_date' => $user->birth_date,
                'gender' => $user->gender,
                'institution' => $user->institution,
                'work_unit' => $user->workUnit->name ?? '-',
                'profile_picture' => $user->profile_picture,
                'photo_url' => $photoUrl,
            ]
        ]);
    }

    /**
     * Show the form for editing user profile
     */
    public function editProfile(User $user)
    {
        if (request()->user()->isNot($user)) {
            abort(403, 'Anda tidak berhak mengedit profil ini.');
        }

        $user->load(['role', 'workUnit']);

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;

        return Inertia::render('User/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'member_number' => $user->member_number,
                'name' => $user->name,
                'nik' => $user->nik,
                'birth_date' => $user->birth_date,
                'gender' => $user->gender,
                'institution' => $user->institution,
                'work_unit' => $user->workUnit->name ?? '-',
                'profile_picture' => $user->profile_picture,
                'photo_url' => $photoUrl,
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request, User $user)
    {
        if ($request->user()->isNot($user)) {
            abort(403, 'Anda tidak berhak memperbarui profil ini.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Ensure NIK is unique except for current user
            'nik' => [
                'required',
                'string',
                'size:16',
                \Illuminate\Validation\Rule::unique('users', 'nik')->ignore($user->id, 'id'),
            ],
            'birth_date' => 'required|date|before_or_equal:today|after_or_equal:1900-01-01',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
        ]);

        $user->update($validated);

        return redirect()->route('user.profile.show', $user)
            ->with('success', 'Profil berhasil diperbarui');
    }

    /**
     * Update user's profile picture
     */
    public function updateProfilePicture(Request $request, User $user)
    {
        if ($request->user()->isNot($user)) {
            abort(403, 'Anda tidak berhak mengubah foto profil ini.');
        }

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $tmpPath = $request->file('profile_picture')->getPathname();
        if (! @getimagesize($tmpPath)) {
            return back()->withErrors(['profile_picture' => 'File tidak valid sebagai gambar.']);
        }

        if ($user->profile_picture && \Storage::disk('public')->exists($user->profile_picture)) {
            \Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new profile picture
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $user->update([
            'profile_picture' => $path
        ]);

        return redirect()->back()->with('success', 'Foto profil berhasil diperbarui');
    }

    /**
     * Delete user's profile picture
     */
    public function deleteProfilePicture(User $user)
    {
        // Authorization: only allow deleting own profile picture
        if (request()->user()->isNot($user)) {
            abort(403, 'Anda tidak berhak menghapus foto profil ini.');
        }

        if ($user->profile_picture && \Storage::disk('public')->exists($user->profile_picture)) {
            \Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update([
            'profile_picture' => null
        ]);

        return redirect()->back()->with('success', 'Foto profil berhasil dihapus');
    }

}
