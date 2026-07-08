<?php

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\RekeningAnggota;
use App\Models\Poin;
use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiSimpananFactory extends Factory
{
    protected $model = TransaksiSimpanan::class;

    public function definition(): array
    {
        return [
            'kode_transaksi_simpanan' => $this->faker->unique()->numerify('ST#####'),
            'nominal_simpanan' => $this->faker->numberBetween(50000, 5000000),
            'tipe_transaksi' => $this->faker->randomElement(TransactionTypeEnum::cases())->value,
            'metode_pembayaran_simpanan' => $this->faker->randomElement(PaymentMethodsEnum::cases())->value,
            'deskripsi_simpanan' => $this->faker->optional()->sentence(),
            'tgl_transaksi' => $this->faker->dateTime(),
            'saldo_setelah_transaksi' => $this->faker->numberBetween(0, 100000000),
            'struk_simpanan' => $this->faker->optional()->filePath(),
            'updated_by' => Pengguna::factory(),
            'akun_simpanan_id' => AkunSimpanan::factory(),
            'no_rekening' => RekeningAnggota::factory(),
            'poin_id' => Poin::factory(),
        ];
    }
}
