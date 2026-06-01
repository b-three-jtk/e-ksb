<?php

namespace App\Services\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Heir;
use App\Models\Member;
use App\Models\MemberDoc;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Log;

class RegisterMemberService
{
    /**
     * Register a new member with heir and optional documents.
     *
     * @param array<string, mixed> $validated
     * @return array{name: mixed, user_code: string, initial_password: string, phone_number: mixed}
     */
    public function register(array $validated, Request $request): array
    {
        $memberNumber = $this->generateMemberNumber();
        $initialPassword = Str::upper(Str::random(4)) . random_int(1000, 9999);

        DB::transaction(function () use ($validated, $request, $memberNumber, $initialPassword) {
            $user = $this->createUser($validated, $memberNumber, $initialPassword);
            $member = $this->createMember($validated, $user->id);

            $user->assignRole('Anggota');

            Log::info("User {$user->id} registered as member with user code {$memberNumber}");
            $this->createMemberHeir($validated, $member->id);
            $this->createMemberDocuments($request, $member->id);
        });

        return [
            'name' => $validated['name'],
            'user_code' => $memberNumber,
            'initial_password' => $initialPassword,
            'phone_number' => $validated['phone_number'],
        ];
    }

    private function generateMemberNumber(): string
    {
        $lastNumeric = User::query()
            ->where('user_code', 'like', 'KSP%')
            ->selectRaw("MAX(CAST(REGEXP_REPLACE(user_code, '[^0-9]', '', 'g') AS INTEGER)) as max_number")
            ->value('max_number');

        $nextNumber = ((int) ($lastNumeric ?? 0)) + 1;

        return 'KSP' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function createUser(array $validated, string $memberNumber, string $initialPassword): User
    {
        $email = $validated['email'] ?? null;

        return User::create([
            'user_code' => $memberNumber,
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'phone_number' => $validated['phone_number'],
            'email' => $email,
            'status' => UserStatusEnum::ACTIVE->value,
            'joined_date' => now()->toDateString(),
            'password' => Hash::make($initialPassword),
        ]);
    }

    private function createMember(array $validated, string $userId): Member
    {
        return Member::create([
            'user_id' => $userId,
            'gender' => $validated['gender'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'marital_status' => $validated['marital_status'],
            'spouse_name' => $validated['spouse_name'] ?? null,
            'domicile_address' => $validated['domicile_address'],
            'residential_address' => $validated['residential_address'] ?? null,
            'last_education' => $validated['last_education'],
            'status' => MemberStatusEnum::PAYMENT_PENDING->value,
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function createMemberHeir(array $validated, string $memberId): void
    {
        Heir::create([
            'heir_nik' => $validated['heir_nik'],
            'heir_name' => $validated['heir_name'],
            'relationship' => $validated['heir_relationship'],
            'heir_contact' => $validated['heir_contact'],
            'member_id' => $memberId,
        ]);
    }

    private function createMemberDocuments(Request $request, string $memberId): void
    {
        if ($request->hasFile('ktp_photo')) {
            $ktpPath = $request->file('ktp_photo')->store('documents', 'public');
            MemberDoc::create([
                'doc_name' => 'ktp',
                'doc_attachment' => $ktpPath,
                'member_id' => $memberId,
            ]);
        }

        if ($request->hasFile('kk_photo')) {
            $kkPath = $request->file('kk_photo')->store('documents', 'public');
            MemberDoc::create([
                'doc_name' => 'kartu_keluarga',
                'doc_attachment' => $kkPath,
                'member_id' => $memberId,
            ]);
        }
    }
}
