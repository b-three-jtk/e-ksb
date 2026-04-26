<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberDoc;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberDocFactory extends Factory
{
    protected $model = MemberDoc::class;

    public function definition(): array
    {
        return [
            'doc_name' => $this->faker->randomElement(['KTP', 'Passport', 'SIM', 'Birth Certificate', 'Marriage Certificate']),
            'doc_attachment' => $this->faker->filePath(),
            'member_id' => Member::factory(),
        ];
    }
}
