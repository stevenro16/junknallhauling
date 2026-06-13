<?php

namespace App\Services;

use App\Models\EquipmentType;
use App\Models\Inquiry;
use App\Models\ServiceCatalog;
use Database\Seeders\DemoInquirySeeder;
use Database\Seeders\EquipmentTypeSeeder;
use Database\Seeders\ServiceCatalogSeeder;

class DemoSeeder
{
    /**
     * Mirrors seedDemoInquiries(): seed catalogs if empty, and demo leads if
     * the table is empty (outside production).
     */
    public static function ensure(): void
    {
        if (ServiceCatalog::count() === 0) {
            (new ServiceCatalogSeeder)->run();
        }
        if (EquipmentType::count() === 0) {
            (new EquipmentTypeSeeder)->run();
        }
        if (Inquiry::count() === 0 && ! app()->environment('production')) {
            (new DemoInquirySeeder)->run();
        }
    }
}
