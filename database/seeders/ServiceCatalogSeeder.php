<?php

namespace Database\Seeders;

use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Mirrors the serviceDefaults block in seedDemoInquiries().
        $services = [
            ['key' => 'junk-removal',  'label' => 'Junk Removal',            'default_price' => 375,  'default_duration_minutes' => 120],
            ['key' => '10yd-dumpster', 'label' => '10 Yard Dumpster Rental', 'default_price' => 275,  'default_duration_minutes' => 90],
            ['key' => '20yd-dumpster', 'label' => '20 Yard Dumpster Rental', 'default_price' => 375,  'default_duration_minutes' => 120],
            ['key' => 'equipment',     'label' => 'Equipment Rental',        'default_price' => 150,  'default_duration_minutes' => 60],
            ['key' => 'other',         'label' => 'Other / Not Sure',        'default_price' => null, 'default_duration_minutes' => 90],
        ];

        foreach ($services as $svc) {
            ServiceCatalog::firstOrCreate(
                ['key' => $svc['key']],
                [
                    'label'                    => $svc['label'],
                    'default_price'            => $svc['default_price'],
                    'default_duration_minutes' => $svc['default_duration_minutes'],
                    'active'                   => true,
                ],
            );
        }
    }
}
