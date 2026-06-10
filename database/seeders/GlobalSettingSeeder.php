<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GlobalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GlobalSetting::factory()->create([
            'key' => 'saving_pokok_amount',
            'value' => '100000',
            'effective_date' => now(),
            'description' => 'Maximum loan amount that can be applied for by members.',
        ]);
        GlobalSetting::factory()->create([
            'key' => 'saving_wajib_amount',
            'value' => '100000',
            'effective_date' => now(),
            'description' => 'Maximum loan amount that can be applied for by members.',
        ]);
        GlobalSetting::factory()->create([
            'key' => 'saving_point_amount',
            'value' => '1',
            'effective_date' => now(),
            'description' => 'Saving point conversion rate, where 1 point is equivalent to a certain amount of money.',
        ]);
        GlobalSetting::factory()->create([
            'key' => 'saving_point_reward',
            'value' => '100000',
            'effective_date' => now(),
            'description' => 'Saving point reward threshold, where members can redeem their points for rewards once they reach this amount.',
        ]);
        GlobalSetting::factory()->create([
            'key' => 'murabahah_margin_percentage',
            'value' => '8',
            'effective_date' => now(),
            'description' => 'Murabahah margin percentage for loan calculations.',
        ]);
    }
}
