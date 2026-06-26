<?php

namespace Database\Seeders;

use App\Models\EquipmentType;
use Illuminate\Database\Seeder;

class EquipmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Mirrors the equipment defaults block in seedDemoInquiries().
        $defaults = [
            ['name' => 'Scissor Lift (19-26 ft)',  'avg_cost_per_hour' => 85],
            ['name' => 'Mini Excavator (3-5 ton)', 'avg_cost_per_hour' => 120],
            ['name' => 'Skid Steer Loader',        'avg_cost_per_hour' => 95],
            ['name' => 'Boom Lift (40-60 ft)',     'avg_cost_per_hour' => 145],
            ['name' => 'Forklift (5-8k lb)',       'avg_cost_per_hour' => 75],
            ['name' => 'Towable Manlift',          'avg_cost_per_hour' => 65],
        ];

        foreach ($defaults as $item) {
            EquipmentType::firstOrCreate(
                ['name' => $item['name']],
                ['avg_cost_per_hour' => $item['avg_cost_per_hour'], 'daily_rate' => null, 'active' => true],
            );
        }

        // Flat-rate rental example: oversized 10-yard dump trailer.
        EquipmentType::firstOrCreate(
            ['name' => 'Oversized 10-Yard Dump Trailer'],
            [
                'flat_price' => 349,
                'included_days' => 7,
                'included_tons' => 1,
                'price_per_additional_ton' => 84,
                'price_per_additional_day' => 15,
                'active' => true,
            ],
        );
    }
}
