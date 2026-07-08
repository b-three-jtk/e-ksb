<?php

namespace Database\Factories;

use App\Enums\ConditionEnum;
use App\Models\Pembiayaan;
use App\Models\ObjekPembiayaan;
use App\Models\JenisBarang;
use App\Models\Pemasok;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObjekPembiayaanFactory extends Factory
{
    protected $model = ObjekPembiayaan::class;

    public function definition(): array
    {
        return [
            'nama_barang' => $this->faker->word(),
            'spesifikasi_barang' => $this->faker->sentence(),
            'kuantitas' => $this->faker->numberBetween(1, 10),
            'kondisi_produk' => $this->faker->randomElement(ConditionEnum::cases())->value,
            'harga_beli_per_unit' => $this->faker->numberBetween(100000, 50000000),
            'struk_pembelian' => null,
            'jenis_barang_id' => JenisBarang::inRandomOrder()->first()?->id ?? JenisBarang::factory(),
            'pemasok_id' => Pemasok::inRandomOrder()->first()?->id ?? Pemasok::factory(),
            'pembiayaan_id' => Pembiayaan::factory(),
        ];
    }
}

