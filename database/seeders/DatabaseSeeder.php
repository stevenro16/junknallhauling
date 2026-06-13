<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            ServiceCatalogSeeder::class,
            EquipmentTypeSeeder::class,
        ]);

        // Demo leads only outside production.
        if (! app()->environment('production')) {
            $this->call(DemoInquirySeeder::class);
        }
    }
}
