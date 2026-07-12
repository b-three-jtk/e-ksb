<?php

namespace Database\Factories;

use App\Enums\AhliWarisEnum;
use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AhliWaris>
 */
class AhliWarisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik_ahli_waris' => fake('id_ID')->unique()->numerify('################'),
            'nama_ahli_waris' => fake('id_ID')->name(),
            'hubungan' => fake('id_ID')->randomElement(AhliWarisEnum::cases())->value,
            'kontak_ahli_waris' => fake('id_ID')->phoneNumber(),
            'anggota_id' => Anggota::factory(),
        ];
    }
}
