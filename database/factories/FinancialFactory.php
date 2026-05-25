<?php

namespace Database\Factories;

use App\Models\Financial;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialFactory extends Factory
{
    protected $model = Financial::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'gaji_pokok_amount' => $this->faker->numberBetween(1000000, 10000000),
            'penghasilan_usaha_amount' => $this->faker->numberBetween(1000000, 10000000),
            'penghasilan_pasangan_amount' => $this->faker->numberBetween(1000000, 10000000),
            'penghasilan_lainnya_amount' => $this->faker->numberBetween(1000000, 10000000),
            'biaya_hidup_keluarga_amount' => $this->faker->numberBetween(100000, 1000000),
            'biaya_pendidikan_amount' => $this->faker->numberBetween(100000, 1000000),
            'jumlah_cicilan_amount' => $this->faker->numberBetween(100000, 1000000),
            'jumlah_biaya_lainnya_amount' => $this->faker->numberBetween(100000, 1000000),
        ];
    }
}
