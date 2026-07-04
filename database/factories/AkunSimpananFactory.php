<?php

namespace Database\Factories;

use App\Enums\SavingTypeEnum;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
use Illuminate\Database\Eloquent\Factories\Factory;

class AkunSimpananFactory extends Factory
{
    protected $model = AkunSimpanan::class;

    public function definition(): array
    {
        return [
            'kode_akun_simpanan' => $this->faker->unique()->numerify('SAV-######'),
            'jenis_simpanan' => $this->faker->randomElement(SavingTypeEnum::cases())->value,
            'saldo' => $this->faker->numberBetween(0, 100000000),
            'anggota_id' => Anggota::factory(),
        ];
    }
}
