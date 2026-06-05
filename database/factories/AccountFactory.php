<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_ref_account' => $this->faker->unique()->numerify('###'),
            'account_name' => $this->faker->word(),
            'account_category' => $this->faker->randomElement(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'balance' => $this->faker->randomFloat(2, 1000, 100000), // Random balance between 1,000
        ];
    }
}
