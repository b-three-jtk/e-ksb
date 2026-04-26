<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberJobFactory extends Factory
{
    protected $model = MemberJob::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'job_title' => $this->faker->jobTitle(),
            'company_or_business_name' => $this->faker->company(),
            'business_field' => $this->faker->randomElement(['Manufacturing', 'Retail', 'Services', 'Technology', 'Agriculture']),
            'tenure_year' => $this->faker->numberBetween(1, 30),
            'workplace_address' => $this->faker->address(),
            'workplace_contact' => $this->faker->phoneNumber(),
        ];
    }
}
