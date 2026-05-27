<?php

namespace App\Http\Controllers\User;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class UserProfileController extends Controller
{

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $user = auth()->user();
        $member = $user->member?->loadMissing(['heirs', 'memberDocs']);

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $heirs = $member?->heirs?->map(function ($heir) {
            return [
                'heir_nik' => $heir->heir_nik,
                'heir_name' => $heir->heir_name,
                'relationship' => $heir->relationship,
                'heir_contact' => $heir->heir_contact,
            ];
        })->values() ?? collect();

        $spouseHeir = $heirs->first(function ($heir) {
            return in_array($heir['relationship'] ?? '', ['Suami', 'Istri'], true);
        });

        $ktpDocument = $member?->memberDocs?->firstWhere('doc_name', 'ktp');
        $kkDocument = $member?->memberDocs?->firstWhere('doc_name', 'kartu_keluarga');

        return Inertia::render('User/Profile/Show', [
            'user' => [
                'id' => $user->id,
                'user_code' => $user->user_code,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'profile_picture' => $user->profile_picture,
                'photo_url' => $photoUrl,
                'role_name' => $user->getRoleNames()->first() ?? 'Anggota',
                'member' => [
                    'gender' => $member?->gender,
                    'birth_place' => $member?->birth_place,
                    'birth_date' => $member?->birth_date
                        ? Carbon::parse($member->birth_date)->translatedFormat('d M Y')
                        : null,
                    'status' => $member?->status,
                    'domicile_address' => $member?->domicile_address,
                    'residential_address' => $member?->residential_address,
                    'marital_status' => $member?->marital_status,
                    'last_education' => $member?->last_education,
                    'dependents' => $member?->dependents,
                    'spouse_name' => $member?->spouse_name ?? $spouseHeir['heir_name'] ?? null,
                    'heirs' => $heirs,
                    'documents' => [
                        'ktp' => $ktpDocument?->doc_attachment ? asset('storage/' . $ktpDocument->doc_attachment) : null,
                        'kk' => $kkDocument?->doc_attachment ? asset('storage/' . $kkDocument->doc_attachment) : null,
                    ],
                ],
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $user = auth()->user();
        $member = $user->member?->loadMissing(['heirs', 'memberDocs']);

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $heirs = $member?->heirs?->map(function ($heir) {
            return [
                'heir_nik' => $heir->heir_nik,
                'heir_name' => $heir->heir_name,
                'relationship' => $heir->relationship,
                'heir_contact' => $heir->heir_contact,
            ];
        })->values() ?? collect();

        $spouseHeir = $heirs->first(function ($heir) {
            return in_array($heir['relationship'] ?? '', ['Suami', 'Istri'], true);
        });

        $ktpDocument = $member?->memberDocs?->firstWhere('doc_name', 'ktp');
        $kkDocument = $member?->memberDocs?->firstWhere('doc_name', 'kartu_keluarga');

        return Inertia::render('User/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'user_code' => $user->user_code,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'profile_picture' => $user->profile_picture,
                'photo_url' => $photoUrl,
                'member' => [
                    'gender' => $member?->gender,
                    'birth_place' => $member?->birth_place,
                    'birth_date' => $member?->birth_date
                        ? Carbon::parse($member->birth_date)->translatedFormat('d M Y')
                        : null,
                    'status' => $member?->status,
                    'domicile_address' => $member?->domicile_address,
                    'residential_address' => $member?->residential_address,
                    'marital_status' => $member?->marital_status,
                    'last_education' => $member?->last_education,
                    'dependents' => $member?->dependents,
                    'spouse_name' => $member?->spouse_name ?? $spouseHeir['heir_name'] ?? null,
                    'heirs' => $heirs,
                    'documents' => [
                        'ktp' => $ktpDocument?->doc_attachment ? asset('storage/' . $ktpDocument->doc_attachment) : null,
                        'kk' => $kkDocument?->doc_attachment ? asset('storage/' . $kkDocument->doc_attachment) : null,
                    ],
                ],
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
            'nik' => [
                'required',
                'string',
                'size:16',
                \Illuminate\Validation\Rule::unique('users', 'nik')->ignore($user->id, 'id'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id, 'id'),
            ],
            'phone_number' => 'nullable|string|max:20',
            'last_education' => 'nullable|string|max:255',
            'residential_address' => 'nullable|string|max:1000',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        if ($user->member) {
            $user->member->update([
                'last_education' => $validated['last_education'] ?? null,
                'residential_address' => $validated['residential_address'] ?? null,
            ]);
        }

        return redirect()->route('user.profile.show');
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

        return redirect()->back();
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

        return redirect()->back();
    }

    /**
     * Update user's password
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('Password saat ini tidak sesuai.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'password.required' => 'Password baru harus diisi.',
            'password.min' => 'Password harus minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung huruf besar dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai dengan password baru.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password berhasil diubah');
    }
}
