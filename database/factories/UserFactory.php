<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_number' => 'KSP' . fake()->unique()->numberBetween(100, 999),
            'nik' => fake()->unique()->numerify('################'),
            'name' => fake()->name(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(['Laki-laki', 'Perempuan']),
            'institution' => fake()->company(),
            'marital_status' => fake()->randomElement(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati']),
            'address' => fake()->address(),
            'residential_address' => fake()->address(),
            'phone_number' => fake()->unique()->numerify('08##########'),
            'last_education' => fake()->randomElement(['TIDAK/BELUM SEKOLAH', 'TAMAT SD/SEDERAJAT', 'SLTP/SEDERAJAT', 'SLTA/SEDERAJAT', 'DIPLOMA I/II', 'AKADEMI/DIPLOMA III/SARJANA MUDA', 'DIPLOMA IV/STRATA I', 'STRATA II', 'STRATA III']),
            'dependents' => fake()->numberBetween(0, 5),
            'status' => fake()->randomElement(['Aktif', 'Tidak Aktif', 'Mengundurkan Diri', 'Dalam Peninjauan', 'Ditolak dengan alasan']),
            'spouse_name' => fake()->optional()->name(),
            'joined_date' => fake()->date(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => fake()->numberBetween(1, 3),
            'work_unit_id' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
