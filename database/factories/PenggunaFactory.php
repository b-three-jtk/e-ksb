<?php

namespace Database\Factories;

use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Pengguna>
 */
class PenggunaFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Sequence for user_code.
     */
    protected static ?int $sequence = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $yymm = date('ym');
        $prefix = 'KSB' . $yymm;

        if (self::$sequence === null) {
            $last = User::query()
                ->where('user_code', 'like', $prefix . '%')
                ->orderBy('user_code', 'desc')
                ->value('user_code');
            
            self::$sequence = $last ? (int) substr($last, -3) : 0;
        }

        self::$sequence++;
        $userCode = $prefix . str_pad((string) self::$sequence, 3, '0', STR_PAD_LEFT);

        return [
            'kode_pengguna' => 'KSB' . date('ym') . fake()->unique()->numerify('###'),
            'nik' => fake()->unique()->numerify('################'),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'no_telp' => fake()->unique()->numerify('08##########'),
            'tgl_bergabung' => fake()->date(),
            'status' => UserStatusEnum::ACTIVE->value,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_diverifikasi_pada' => null,
        ]);
    }

    /**
     * Create user with specific role.
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function ($user) use ($role) {
            $user->assignRole($role);
        });
    }
}
