<?php

namespace Database\Factories;

use App\Enums\PositionEnum;
use App\Models\Akun;
use App\Models\DetailJurnal;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetailJurnalFactory extends Factory
{
    protected $model = DetailJurnal::class;

    public function definition(): array
    {
        return [
            'no_ref_akun' => Akun::factory(),
            'posisi_akun' => $this->faker->randomElement(PositionEnum::cases())->value,
            'nominal' => $this->faker->numberBetween(10000, 100000000),
            'updated_by' => Pengguna::factory(),
        ];
    }
}
