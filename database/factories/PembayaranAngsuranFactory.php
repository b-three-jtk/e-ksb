<?php

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Models\Angsuran;
use App\Models\PembayaranAngsuran;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

class PembayaranAngsuranFactory extends Factory
{
    protected $model = PembayaranAngsuran::class;

    public function definition(): array
    {
        return [
            'kode_transaksi_pembayaran' => $this->faker->unique()->numerify('IPT-#########'),
            'metode_pembayaran' => $this->faker->randomElement(PaymentMethodsEnum::cases())->value,
            'is_pelunasan_lebih_cepat' => $this->faker->boolean(),
            'jumlah_angsuran_dibayar' => $this->faker->numberBetween(100000, 10000000),
            'tgl_pembayaran' => $this->faker->dateTime(),
            'angsuran_id' => Angsuran::factory(),
            'updated_by' => Pengguna::factory(),
            'struk_pembayaran' => $this->faker->optional()->filePath(),
        ];
    }
}
