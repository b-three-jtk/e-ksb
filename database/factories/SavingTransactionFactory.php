<?php

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\MemberBankAccount;
use App\Models\PointTransaction;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingTransactionFactory extends Factory
{
    protected $model = SavingTransaction::class;

    public function definition(): array
    {
        return [
            'saving_transaction_code' => $this->faker->unique()->numerify('ST#####'),
            'saving_amount' => $this->faker->numberBetween(50000, 5000000),
            'transaction_type' => $this->faker->randomElement(TransactionTypeEnum::cases())->value,
            'saving_payment_method' => $this->faker->randomElement(PaymentMethodsEnum::cases())->value,
            'saving_description' => $this->faker->optional()->sentence(),
            'transaction_date' => $this->faker->dateTime(),
            'balance_after_transaction' => $this->faker->numberBetween(0, 100000000),
            'saving_transaction_receipt' => $this->faker->optional()->filePath(),
            'updated_by' => User::factory(),
            'saving_account_id' => SavingAccount::factory(),
            'account_number' => MemberBankAccount::factory(),
            'point_id' => PointTransaction::factory(),
        ];
    }
}
