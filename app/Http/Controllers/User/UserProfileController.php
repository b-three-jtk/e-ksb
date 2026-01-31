<?php

namespace App\Http\Controllers\User;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserProfileController extends Controller
{

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $user = auth()->user();
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
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $user = auth()->user();
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
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

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

        return redirect()->route('user.profile.show')
            ->with('success', 'Profil berhasil diperbarui');
    }

    /**
     * Update user's profile picture
     */
    public function updateProfilePicture(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $tmpPath = $request->file('profile_picture')->getPathname();
        if (!@getimagesize($tmpPath)) {
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
    public function deleteProfilePicture()
    {
        $user = auth()->user();

        if ($user->profile_picture && \Storage::disk('public')->exists($user->profile_picture)) {
            \Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update([
            'profile_picture' => null
        ]);

        return redirect()->back()->with('success', 'Foto profil berhasil dihapus');
    }
}
