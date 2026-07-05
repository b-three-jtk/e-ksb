<?php

namespace Database\Factories;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Angsuran;
use Illuminate\Database\Eloquent\Factories\Factory;

class AngsuranFactory extends Factory
{
    protected $model = Angsuran::class;

    public function definition(): array
    {
        return [
            'tgl_jatuh_tempo' => $this->faker->dateTimeBetween('now', '+1 year'),
            'angsuran_ke' => $this->faker->numberBetween(1, 36),
            'nominal_angsuran' => $this->faker->randomFloat(2, 100000, 1000000),
            'status' => $this->faker->randomElement(array_column(InstallmentPaymentScheduleStatusEnum::cases(), 'value')),
            'pembiayaan_id' => Pembiayaan::factory(),
        ];
    }

    /**
     * 12 months tenor
     */
    public function tenor12(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenor' => 12,
        ]);
    }

    /**
     * 24 months tenor
     */
    public function tenor24(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenor' => 24,
        ]);
    }

    /**
     * 36 months tenor
     */
    public function tenor36(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenor' => 36,
        ]);
    }
}

