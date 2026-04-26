<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberBankAccountFactory extends Factory
{
    protected $model = MemberBankAccount::class;

    public function definition(): array
    {
        return [
            'account_number' => $this->faker->unique()->numerify('####################'),
            'bank_name' => $this->faker->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI', 'Maybank', 'CIMB']),
            'account_name' => $this->faker->name(),
            'member_id' => Member::factory(),
        ];
    }
}
