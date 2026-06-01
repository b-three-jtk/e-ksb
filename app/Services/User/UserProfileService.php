<?php

namespace App\Services\User;

use App\Models\PointTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    public function buildProfilePayload(User $user): array
    {
        $member = $user->member?->loadMissing(['heirs', 'memberDocs']);
        $pointTransactions = $user->pointTransactions()
            ->with('savingTransactions')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $getSnapshotValue = function (PointTransaction $transaction): float {
            return (float) ($transaction->saving_balance_snapshot
                ?? $transaction->savingTransactions?->balance_after_transaction
                ?? 0);
        };

        $runningPointTotal = 0;
        $pointHistory = $pointTransactions
            ->map(function (PointTransaction $transaction) use (&$runningPointTotal) {
                $runningPointTotal += (int) $transaction->amount_earned;

                return [
                    'id' => $transaction->id,
                    'calculation_date' => $transaction->calculation_period
                        ? Carbon::parse($transaction->calculation_period)->translatedFormat('d/m/Y')
                        : Carbon::parse($transaction->created_at)->format('d/m/Y'),
                    'total_simpanan' => $getSnapshotValue($transaction),
                    'points_earned' => (int) $transaction->amount_earned,
                    'total_points' => $runningPointTotal,
                    'activity_description' => $transaction->activity_description,
                ];
            })
            ->reverse()
            ->values();

        $latestPointTransaction = $pointTransactions->last();

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

        return [
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
            'points' => [
                'summary' => [
                    'total_points' => (int) $pointTransactions->sum('amount_earned'),
                    'latest_points_earned' => (int) ($latestPointTransaction?->amount_earned ?? 0),
                    'latest_calculated_at' => $latestPointTransaction?->calculation_period
                        ? Carbon::parse($latestPointTransaction->calculation_period)->translatedFormat('d/m/Y')
                        : ($latestPointTransaction?->created_at
                            ? Carbon::parse($latestPointTransaction->created_at)->format('d/m/Y')
                            : null),
                    'latest_total_simpanan' => $latestPointTransaction
                        ? $getSnapshotValue($latestPointTransaction)
                        : 0,
                ],
                'history' => $pointHistory,
            ],
        ];
    }

    public function updateProfile(User $user, array $validated): void
    {
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
    }

    public function updateProfilePicture(User $user, UploadedFile $profilePicture): void
    {
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $profilePicture->store('profile_pictures', 'public');

        $user->update([
            'profile_picture' => $path,
        ]);
    }

    public function deleteProfilePicture(User $user): void
    {
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update([
            'profile_picture' => null,
        ]);
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);
    }
}
