<?php

namespace App\Services\Admin;

use App\Enums\UserStatus;
use App\Models\Heir;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class RegisterMemberService
{
    /**
     * Register a new member with heir and optional documents.
     *
     * @param array<string, mixed> $validated
     * @return array{name: mixed, member_code: string, initial_password: string, phone_number: mixed}
     */
    public function register(array $validated, Request $request): array
    {
        $memberRole = Role::where('name', 'Anggota')->first();

        if (!$memberRole) {
            throw new RuntimeException('Role Anggota tidak ditemukan. Hubungi administrator.');
        }

        $memberNumber = $this->generateMemberNumber();
        $initialPassword = Str::upper(Str::random(4)) . random_int(1000, 9999);

        DB::transaction(function () use ($validated, $request, $memberRole, $memberNumber, $initialPassword) {
            $user = $this->createMemberUser($validated, $memberRole->id, $memberNumber, $initialPassword);

            $this->createMemberHeir($validated, $user->id);
            $this->createMemberDocuments($request, $user->id);
        });

        return [
            'name' => $validated['name'],
            'member_code' => $memberNumber,
            'initial_password' => $initialPassword,
            'phone_number' => $validated['phone_number'],
        ];
    }

    private function generateMemberNumber(): string
    {
        $lastNumeric = User::query()
            ->where('member_code', 'like', 'KSP%')
            ->selectRaw("MAX(CAST(REGEXP_REPLACE(member_code, '[^0-9]', '', 'g') AS INTEGER)) as max_number")
            ->value('max_number');

        $nextNumber = ((int) ($lastNumeric ?? 0)) + 1;

        return 'KSP' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function createMemberUser(array $validated, string $roleId, string $memberNumber, string $initialPassword): User
    {
        $email = $validated['email'] ?? null;

        return User::create([
            'member_code' => $memberNumber,
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'gender' => $validated['gender'],
            'marital_status' => $validated['marital_status'],
            'phone_number' => $validated['phone_number'],
            'email' => $email,
            'address' => $validated['address'],
            'residential_address' => $validated['residential_address'] ?? null,
            'last_education' => $validated['last_education'],
            'status' => UserStatus::ACTIVE->value,
            'joined_date' => now()->toDateString(),
            'password' => Hash::make($initialPassword),
            'role_id' => $roleId,
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function createMemberHeir(array $validated, string $userId): void
    {
        Heir::create([
            'nik' => $validated['heir_nik'],
            'name' => $validated['heir_name'],
            'relationship' => $validated['heir_relationship'],
            'contact' => $validated['heir_contact'],
            'user_id' => $userId,
        ]);
    }

    private function createMemberDocuments(Request $request, string $userId): void
    {
        if ($request->hasFile('ktp_photo')) {
            $ktpPath = $request->file('ktp_photo')->store('user_docs', 'public');
            UserDoc::create([
                'name' => 'ktp',
                'attachment' => $ktpPath,
                'user_id' => $userId,
            ]);
        }

        if ($request->hasFile('kk_photo')) {
            $kkPath = $request->file('kk_photo')->store('user_docs', 'public');
            UserDoc::create([
                'name' => 'kk',
                'attachment' => $kkPath,
                'user_id' => $userId,
            ]);
        }
    }
}
