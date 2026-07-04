<?php

namespace Database\Factories;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Models\Anggota;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pembiayaan>
 */
class PembiayaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = $this->faker->numberBetween(5000000, 50000000);
        $marginAmount = $this->faker->numberBetween(1000000, 10000000);
        $downPayment = $this->faker->numberBetween(500000, min(5000000, $costPrice));

        return [
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'harga_perolehan' => $costPrice,
            'margin_keuntungan' => $marginAmount,
            'uang_muka' => $downPayment,
            'tgl_permohonan' => $this->faker->dateTimeBetween('-6 months', '-3 months')->format('Y-m-d'),
            'tgl_akad' => $this->faker->dateTimeBetween('-3 months', '-1 month')->format('Y-m-d'),
            'tgl_lunas' => null,
            'metode_pembayaran' => $this->faker->randomElement(FinancingPaymentMethodEnum::cases())->value,
            'dokumen_akad' => null,

            'updated_by' => Pengguna::inRandomOrder()->first()?->id ?? Pengguna::factory(),
            'anggota_id' => Anggota::inRandomOrder()->first()?->id ?? Anggota::factory()->create()->id,
        ];
    }

    /**
     * Active pembiayaan with installments.
     */
    public function activeInstallments(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);
    }

    /**
     * Paid/completed pembiayaan.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancingReqStatusEnum::PAID->value,
            'tgl_lunas' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Waiting for documents.
     */
    public function waitingDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
        ]);
    }
}

