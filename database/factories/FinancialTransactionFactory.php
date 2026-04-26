<?php

namespace Database\Factories;

use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialTransactionFactory extends Factory
{
    protected $model = FinancialTransaction::class;

    public function definition(): array
    {
        return [
            'receipt_number' => $this->faker->unique()->numerify('FT-#########'),
            'transaction_date' => $this->faker->dateTime(),
            'description' => $this->faker->optional()->sentence(),
            'transaction_receipt' => $this->faker->optional()->filePath(),
            'updated_by' => User::factory(),
            'source_type' => $this->faker->randomElement(['SavingTransaction', 'InstallmentPaymentTransaction', 'AmdkTransaction']),
            'source_id' => $this->faker->uuid(),
        ];
    }
}
