<?php

namespace Database\Factories;

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'member_code' => 'KSP' . fake()->unique()->numberBetween(100, 999),
            'nik' => fake()->unique()->numerify('################'),
            'name' => fake()->name(),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(GenderEnum::cases())->value,
            'marital_status' => fake()->randomElement(MaritalStatusEnum::cases())->value,
            'domicile_address' => fake()->address(),
            'residential_address' => fake()->address(),
            'phone_number' => fake()->unique()->numerify('08##########'),
            'last_education' => fake()->randomElement(EducationEnum::cases())->value,
            'dependents' => fake()->numberBetween(0, 5),
            'status' => fake()->randomElement(UserStatusEnum::cases())->value,
            'spouse_name' => fake()->optional()->name(),
            'joined_date' => fake()->date(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => fake()->numberBetween(1, 9),
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
