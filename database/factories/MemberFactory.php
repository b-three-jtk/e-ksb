<?php

namespace Database\Factories;

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Models\Anggota;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Anggota::class;

    public function definition(): array
    {
        return [
            'pengguna_id' => Pengguna::factory(),
            'jenis_kelamin' => $this->faker->randomElement(GenderEnum::cases())->value,
            'tempat_lahir' => $this->faker->city(),
            'tgl_lahir' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'status' => MemberStatusEnum::ACTIVE->value,
            'alamat_domisili' => $this->faker->address(),
            'alamat_ktp' => $this->faker->address(),
            'status_pernikahan' => $this->faker->randomElement(MaritalStatusEnum::cases())->value,
            'pendidikan_terakhir' => $this->faker->randomElement(EducationEnum::cases())->value,
            'jml_tanggungan' => $this->faker->numberBetween(0, 5),
        ];
    }

    /**
     * Indicate that the anggota is waiting for payment.
     */
    public function waitingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MemberStatusEnum::PAYMENT_PENDING->value,
        ]);
    }

    /**
     * Indicate that the anggota is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);
    }
}

