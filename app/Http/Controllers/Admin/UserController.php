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
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

    public function verificationDetail(string $id)
    {
        $user = User::with(['workUnit:id,name', 'userDocs'])->findOrFail($id);

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $idCard = $user->userDocs
            ->first(fn ($doc) => strtolower($doc->name) === 'ktp');
        $idCardUrl = $idCard?->attachment ? asset('storage/' . $idCard->attachment) : null;

        return Inertia::render('Admin/User/Verification/Show', [
            'member' => [
                'id' => $user->id,
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

    public function submitApproval(Request $request, string $id)
    {
        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'note' => 'nullable|string',
        ]);

        $user = User::findOrFail($id);
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
    public function profile(string $id)
    {
        $user = User::with(['role', 'workUnit'])->findOrFail($id);
        
        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        
        return Inertia::render('User/Profile/Show', [
            'user' => [
                'id' => $user->id,
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
    public function editProfile(string $id)
    {
        $user = User::with(['role', 'workUnit'])->findOrFail($id);
        
        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        
        return Inertia::render('User/Profile/Edit', [
            'user' => [
                'id' => $user->id,
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
    public function updateProfile(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:16',
            'birth_date' => 'required|date',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
        ]);

        $user = User::findOrFail($id);
        $user->update($validated);

        return redirect()->route('user.profile.show', $id)
            ->with('success', 'Profil berhasil diperbarui');
    }

    /**
     * Update user's profile picture
     */
    public function updateProfilePicture(Request $request, string $id)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::findOrFail($id);

        // Delete old profile picture if exists
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
    public function deleteProfilePicture(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->profile_picture && \Storage::disk('public')->exists($user->profile_picture)) {
            \Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update([
            'profile_picture' => null
        ]);

        return redirect()->back()->with('success', 'Foto profil berhasil dihapus');
    }
}
